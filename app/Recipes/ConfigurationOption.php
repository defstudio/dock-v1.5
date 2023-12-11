<?php

/** @noinspection PhpDocSignatureIsNotCompleteInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Enums\EnvKey;
use BackedEnum;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnitEnum;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ConfigurationOption
{
    protected EnvKey $key;

    protected string $description;

    protected string $question;

    protected string|int|bool $value;

    /** @var string[]|int|bool|Closure(Configuration): (string|int|bool) */
    protected array|string|int|bool|Closure $defaultValue = '';

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

    /** @var (Closure(Configuration): array<array-key, string|int|bool|BackedEnum|UnitEnum>)|array<array-key, string|int|bool|BackedEnum|UnitEnum> */
    protected Closure|array $choices = [];

    protected bool $multiple = false;

    public static function make(EnvKey $key, string|int|bool $value = null): self
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
     * @param (Closure(Configuration $configuration): array<array-key, string|int|bool|BackedEnum|UnitEnum>)|array<array-key, string|int|bool|BackedEnum|UnitEnum> $choices
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

        $this->ask($configuration);
        $this->normalizeValue($configuration);

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

    protected function computeDefaultValue(Configuration $configuration): string|array
    {
        $default = $this->defaultValue instanceof Closure
            ? call_user_func($this->defaultValue, $configuration)
            : $this->defaultValue;

        if($this->multiple && !is_array($default)){
            if(empty($default)){
                return [];
            }

            return explode(',', $default);
        }

        return match ($default) {
            true => 'yes',
            false => 'no',
            default => "$default",
        };
    }

    protected function computeChoices(Configuration $configuration): array
    {
        /** @var array<array-key, string|int|bool|BackedEnum|UnitEnum> $choices */
        $choices = is_array($this->choices)
            ? $this->choices
            : call_user_func($this->choices, $configuration);

        return collect($choices)
            ->map(function ($choice) {
                if ($choice instanceof BackedEnum) {
                    return $choice->value;
                }

                if ($choice instanceof UnitEnum) {
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

        if (count($choices) === 0) {
            $this->value = text(
                $this->question ?? $this->description,
                default: $this->computeDefaultValue($configuration),
                required: $this->required,
                validate: fn (string $value) => $this->apply_validation($configuration, $value, !$this->required),
                hint: ($this->question ?? null) !== ($this->description ?? null) ? $this->description ?? '' : '',
            );

            return;
        }

        if (!$this->multiple) {
            $this->value = select(
                $this->question ?? $this->description,
                $choices,
                $this->computeDefaultValue($configuration),
                validate: fn (string $value) => $this->apply_validation($configuration, $value, !$this->required),
                hint: ($this->question ?? null) !== ($this->description ?? null) ? $this->description ?? '' : '',
                required: $this->required,
            ) ?? '';

            return;
        }

        $this->value = implode(',', multiselect(
            $this->question ?? $this->description,
            $choices,
            $this->computeDefaultValue($configuration),
            required: $this->required,
            validate: fn (array $values) => $this->apply_validation($configuration, $values, !$this->required),
            hint: ($this->question ?? null) !== ($this->description ?? null) ? $this->description ?? 'Use the space bar to select options.' : 'Use the space bar to select options.',
        ));
    }

    protected function apply_validation(Configuration $configuration, array|string|int|bool $value = null, bool $optional = null): string|null
    {
        $value ??= $this->value;
        $optional ??= !$this->required;

        if (empty($value) && $optional) {
            return null;
        }

        if (blank($value)) {
            return 'A value is required';
        }

        $values = Arr::wrap($value);

        foreach ($values as $value){
            if (!empty($this->validationClosure)) {
                /** @var bool|string $validation */
                $validation = call_user_func($this->validationClosure, $value, $configuration);

                if (is_string($validation)) {
                    return "$validation";
                }

                if (!$validation) {
                    return "[$value] is not a valid value";
                }

                return null;
            }

            if (!empty($this->choices) && !$this->multiple && !in_array($value, $this->computeChoices($configuration))) {
                return "[$value] is not a valid value";
            }
        }

        return null;
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

    public function key(): EnvKey
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
