<?php

declare(strict_types=1);

namespace App\Recipes;

use App\Facades\Terminal;
use Illuminate\Support\Str;

abstract class Recipe
{
    abstract public function name(): string;

    public function slug(): string
    {
        return Str::slug($this->name());
    }

    public function setup(): Configuration
    {
        $configuration = new Configuration(collect($this->options()));
        $configuration->configure();
        $configuration->writeEnv($this->slug());

        Terminal::successBanner('The configuration has been stored in .env file');

        return $configuration;
    }

    /**
     * @return ConfigurationSection[]
     */
    abstract public function options(): array;
}
