<?php

namespace App\Recipes;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class Recipe
{
    abstract public function name(): string;

    public function slug(): string
    {
        return Str::slug($this->name());
    }

    public function setup(): void
    {
        $configuration = new Configuration(collect($this->options()));

        $configuration->setup();
    }

    /**
     * @return ConfigurationSection[]
     */
    abstract public function options(): array;
}
