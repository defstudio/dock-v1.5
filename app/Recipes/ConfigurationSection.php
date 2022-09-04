<?php

namespace App\Recipes;

use Illuminate\Support\Collection;

class ConfigurationSection
{
    private string $name;

    /** @var Collection<int, ConfigurationOption> */
    private Collection $options;


    private function __construct()
    {
        $this->options = new Collection();
    }

    /**
     * @param ConfigurationOption[] $options
     */
    public static function make(string $name, array $options): self
    {
        $section = new self();

        $section->name = $name;
        $section->options = collect($options);

        return $section;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function options(): Collection
    {
        return $this->options;
    }
}
