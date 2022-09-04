<?php
/** @noinspection PhpDocSignatureIsNotCompleteInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Facades\Terminal;
use Closure;
use Illuminate\Support\Str;

class ConfigurationOption
{
    protected string $key;
    protected string $description;
    protected string $question;
    protected string|int|bool $value;
    protected string|int|bool $defaultValue = '';
    protected bool $required = true;
    protected bool $confirm = false;
    protected bool $hidden = false;

    /** @var Closure(string|int|bool $value, Configuration $configuration): (bool|string) */
    protected bool|Closure $when = true;

    /** @var Closure(string|int|bool $value, Configuration $configuration): (bool|string) */
    protected Closure $validationClosure;

    /** @var Closure(string|int|bool $value, Configuration $configuration): bool */
    protected Closure $afterSet;


    /** @var string[] */
    protected array $choices = [];

    public static function make(string $key): self
    {
        $option = app(self::class);

        $option->key = $key;

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

    public function default(string|int|bool $default): self
    {
        $this->defaultValue = $default;
        return $this;
    }

    public function optional(): self
    {
        $this->required = false;
        return $this;
    }

    public function hidden(): self
    {
        $this->hidden = true;
        return $this;
    }

    public function choices(array $choices): self
    {
        $this->choices = $choices;
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
     * @param Closure(string|int|bool $value, Configuration $configuration): bool $closure
     */
    public function afterSet(Closure $closure): self
    {
        $this->afterSet = $closure;
        return $this;
    }

    public function setup(Configuration $configuration): void
    {
        if (!$this->isActive($configuration)) {
            return;
        }

        while (!isset($this->value) || !$this->valid($configuration)) {
            $this->ask();
            $this->normalizeValue();
        }

        $this->notifyValueSet($configuration);
    }

    protected function notifyValueSet(Configuration $configuration): void
    {
        if (isset($this->afterSet)) {
            call_user_func($this->afterSet, $this->value(), $configuration);
        }
    }

    protected function normalizeValue(): void
    {
        if (!empty($this->defaultValue) && empty($this->value)) {
            $this->value = $this->computeDefaultValue();
        }

        if (!$this->required && !empty($this->defaultValue) && in_array($this->value, ['x', 'X'])) {
            $this->value = '';
        }

        if ($this->confirm) {
            $this->value = match (Str::of($this->value)->lower()->toString()) {
                'yes' => true,
                'no' => false,
            };
        }
    }

    protected function computeDefaultValue(): string|int
    {
        return match ($this->defaultValue) {
            true => 'yes',
            false => 'no',
            default => $this->defaultValue,
        };
    }

    protected function computeChoices(): array
    {
        return collect($this->choices)
                ->map(fn (string|int|bool $choice) => match ($choice) {
                    true => 'yes',
                    false => 'no',
                    default => $choice,
                })->toArray();
    }

    protected function ask(): void
    {
        $this->value = empty($this->choices)
            ? Terminal::ask($this->question ?? $this->description, $this->computeDefaultValue(), !$this->required)
            : Terminal::choose($this->question ?? $this->description, $this->computeChoices(), $this->computeDefaultValue(), !$this->required);
    }

    protected function valid(Configuration $configuration): bool
    {
        if (empty($this->value) && !$this->required) {
            return true;
        }

        if (blank($this->value)) {
            Terminal::render('<div class="mx-5 mb-1"><span class="text-red font-bold">Error:</span> A value is required');
            return false;
        }

        if (!empty($this->choices) && !in_array($this->value, $this->choices)) {
            Terminal::render("<div class='mx-5 mb-1'><span class='text-red font-bold'>Error:</span> [$this->value] is not a valid value");
            return false;
        }

        if (!empty($this->validationClosure)) {
            /** @var bool|string $validation */
            $validation = call_user_func($this->validationClosure, $this->value, $configuration);

            if (is_string($validation)) {
                Terminal::render("<div class='mx-5 mb-1'><span class='text-red font-bold'>Error:</span> $validation");
                return false;
            }

            if (!$validation) {
                Terminal::render("<div class='mx-5 mb-1'><span class='text-red font-bold'>Error:</span> [$this->value] is not a valid value");
                return false;
            }
        }

        return true;
    }

    protected function isActive(Configuration $configuration): bool
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
}
