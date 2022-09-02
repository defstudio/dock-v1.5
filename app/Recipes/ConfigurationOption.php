<?php

namespace App\Recipes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use function Termwind\ask;
use function Termwind\render;

class ConfigurationOption
{
    private string $key;
    private string $description;
    private string $question;
    private string $value;
    private string $defaultValue = '';
    private bool $required = true;
    private bool $confirm = false;
    private bool $hidden = false;
    private bool|Closure $when = true;

    /** @var Closure(string $value): bool */
    private Closure $validationClosure;

    /** @var Closure(Configuration $configuration): bool */
    private Closure $afterSet;


    /** @var string[] */
    private array $choices = [];


    public function __construct()
    {

    }

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
        $this->choices = ['yes', 'no'];
        return $this;
    }

    public function default(string $default): self
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
     * @param Closure(string $value): bool $closure
     */
    public function validate(Closure $closure): self
    {
        $this->validationClosure = $closure;
        return $this;
    }

    /**
     * @param Closure(Configuration $configuration): bool $closure
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

        while (!$this->valid()) {
            $this->ask();

            if ($this->optional() && in_array($this->value, ['x', 'X'])) {
                $this->value = '';
            }
        }

        if ($this->confirm) {
            $this->value = match ($this->value) {
                'yes' => 1,
                'no' => 0,
            };
        }

        if (isset($this->afterSet)) {
            call_user_func($this->afterSet, $configuration);
        }
    }


    private function ask(): void
    {
        $defaultHint = empty($this->choices)
            ? "<span class='text-white'>$this->defaultValue</span>"
            : collect($this->choices)->map(fn (string $choice) => $choice === $this->defaultValue ? "<span class='text-white'>$choice</span>" : $choice)->join(", ");

        $question = Str::of("<ul class='mx-2'><li><span class='text-green'>")
            ->append($this->question ?? $this->description)
            ->append("</span>")
            ->when($defaultHint, fn (Stringable $str) => $str->append(" <span class='text-gray'>[$defaultHint]</span>"))
            ->append("<span class='text-green'>:</span></li></ul>")
            ->when($this->defaultValue && !$this->required, fn (Stringable $str) => $str->append(" <span class='text-gray'>(press 'x' to skip)</span>"));


        $this->value = ask($question) ?? $this->defaultValue;
    }

    private function valid(): bool
    {
        if (!isset($this->value)) {
            return false;
        }

        if (empty($this->value) && $this->required) {
            render('<div class="mx-5 mb-1"><span class="text-red font-bold">Error:</span> A value is required');
            return false;
        }

        if (!empty($this->choices) && !in_array($this->value, $this->choices)) {
            render("<div class='mx-5 mb-1'><span class='text-red font-bold'>Error:</span> [$this->value] is not a valid value");
            return false;
        }

        if (!empty($this->validationClosure) && !call_user_func($this->validationClosure, $this->value)) {
            return false;
        }

        return true;
    }

    private function isActive(Configuration $configuration): bool
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

    public function value(): string
    {
        return $this->value;
    }
}
