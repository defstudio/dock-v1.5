<?php

/** @noinspection PhpDocSignatureIsNotCompleteInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Facades\Terminal;
use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use UnitEnum;

class ConfigurationOption
{
    protected string $key;

    protected string $description;

    protected string $question;

    protected string|int|bool $value;

    /** @var string|int|bool|Closure(Configuration): (string|int|bool) */
    protected string|int|bool|Closure $defaultValue = '';

    protected bool $required = true;

    protected bool $exportIfEmpty = false;

    protected bool $confirm = false;

    protected bool $hidden = false;

    /** @var bool|Closure(string|int|bool, Configuration): (bool|string) */
    protected bool|Closure $when = true;

    /** @var Closure(string|int|bool, Configuration): (bool|string) */
    protected Closure $validationClosure;

    /** @var Closure(string|int|bool, Configuration): void */
    protected Closure $afterSet;

    /** @var (Closure(Configuration): array<array-key, string|int|bool|BackedEnum|UnitEnum>)|array<array-key, string|int|bool> */
    protected Closure|array $choices = [];

    protected bool $multiple = false;

    public static function make(string $key, string|int|bool $value = null): self
    {
        $option = app(self::class);

        $option->key = $key;

        if ($value !== null) {
            $option->value = $value;
        }

        return $option;
    }

    public function when(bool|Closure $condition): self
    {
        $this->when = $condition;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function question(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function confirm(): self
    {
        $this->confirm = true;
        $this->choices = [true, false];

        return $this;
    }

    public function default(string|int|bool|Closure $default): self
    {
        $this->defaultValue = $default;

        return $this;
    }

    public function optional(bool $exportIfEmpty = false): self
    {
        $this->required = false;
        $this->exportIfEmpty = $exportIfEmpty;

        return $this;
    }

    public function hidden(): self
    {
        $this->hidden = true;

        return $this;
    }

    /**
     * @param (Closure(Configuration $configuration): array<array-key, string|int|bool>)|array<array-key, string|int|bool> $choices
     */
    public function choices(array|Closure $choices, bool $multiple = false): self
    {
        $this->choices = $choices;
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * @param Closure(string|int|bool $value, Configuration $configuration): (bool|string) $closure
     */
    public function validate(Closure $closure): self
    {
        $this->validationClosure = $closure;

        return $this;
    }

    /**
     * @param Closure(string|int|bool $value, Configuration $configuration): void $closure
     */
    public function afterSet(Closure $closure): self
    {
        $this->afterSet = $closure;

        return $this;
    }

    public function configure(Configuration $configuration): void
    {
        if ($this->hasASettedValue()) {
            return;
        }

        if (!$this->isActive($configuration)) {
            return;
        }

        while (!isset($this->value) || !$this->valid($configuration)) {
            $this->ask($configuration);
            $this->normalizeValue($configuration);
        }

        if ($this->confirm && !is_bool($this->value)) {
            $this->value = match (Str::of("$this->value")->lower()->toString()) {
                'yes' => true,
                'no' => false,
                default => $this->value,
            };
        }

        $this->notifyValueSet($configuration);
    }

    protected function notifyValueSet(Configuration $configuration): void
    {
        if (isset($this->afterSet)) {
            call_user_func($this->afterSet, $this->value(), $configuration);
        }
    }

    protected function normalizeValue(Configuration $configuration): void
    {
        $default = $this->computeDefaultValue($configuration);

        if (!empty($default) && empty($this->value)) {
            $this->value = $default;
        }

        if (!$this->required && !empty($default) && in_array($this->value, ['x', 'X'])) {
            $this->value = '';
        }
    }

    protected function computeDefaultValue(Configuration $configuration): string
    {
        $default = $this->defaultValue instanceof Closure
            ? call_user_func($this->defaultValue, $configuration)
            : $this->defaultValue;

        return match ($default) {
            true => 'yes',
            false => 'no',
            default => "$default",
        };
    }

    protected function computeChoices(Configuration $configuration): array
    {
        /** @var array<array-key, string|int|bool> $choices */
        $choices = is_array($this->choices)
            ? $this->choices
            : call_user_func($this->choices, $configuration);

        return collect($choices)
            ->map(function($choice){
                if($choice instanceof BackedEnum){
                    return $choice->value;
                }

                if($choice instanceof UnitEnum){
                    return $choice->name;
                }

                return $choice;
            })
            ->map(fn (string|int|bool $choice) => match ($choice) {
                true => 'yes',
                false => 'no',
                default => $choice,
            })->toArray();
    }

    protected function ask(Configuration $configuration): void
    {
        $choices = $this->computeChoices($configuration);

        if (empty($choices)) {
            $this->value = Terminal::ask(
                $this->question ?? $this->description,
                $this->computeDefaultValue($configuration),
                !$this->required
            );

            return;
        }

        if (!$this->multiple) {
            $this->value = Terminal::choose($this->question ?? $this->description, $choices, $this->computeDefaultValue($configuration), !$this->required) ?? '';

            return;
        }

        $values = [];
        while (!empty($choices)) {
            while (!isset($value) || !$this->valid($configuration, $value, true)) {
                $value = Terminal::choose($this->question ?? $this->description, $choices, allowEmpty: '') ?? '';
            }

            if (empty($value)) {
                break;
            }

            $values[] = $value;

            unset($choices[array_search($value, $choices)]);

            unset($value);
        }

        $this->value = collect($values)->join(',');
    }

    protected function valid(Configuration $configuration, string|int|bool $value = null, bool $optional = null): bool
    {
        $value ??= $this->value;
        $optional ??= !$this->required;

        if (empty($value) && $optional) {
            return true;
        }

        if (blank($value)) {
            Terminal::error('A value is required');

            return false;
        }

        if (!empty($this->validationClosure)) {
            /** @var bool|string $validation */
            $validation = call_user_func($this->validationClosure, $value, $configuration);

            if (is_string($validation)) {
                Terminal::error("$validation");

                return false;
            }

            if (!$validation) {
                Terminal::error("[$value] is not a valid value");

                return false;
            }

            return true;
        }

        if (!empty($this->choices) && !$this->multiple && !in_array($value, $this->computeChoices($configuration))) {
            Terminal::error("[$value] is not a valid value");

            return false;
        }

        return true;
    }

    public function hasASettedValue(): bool
    {
        return isset($this->value);
    }

    public function isActive(Configuration $configuration): bool
    {
        if (is_bool($this->when)) {
            return $this->when;
        }

        return call_user_func($this->when, $configuration);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string|int|bool
    {
        return $this->value ?? '';
    }

    public function shouldShowInEnv(): bool
    {
        return !$this->hidden;
    }

    public function shouldExportIfEmpty(): bool
    {
        return $this->exportIfEmpty;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }
}
