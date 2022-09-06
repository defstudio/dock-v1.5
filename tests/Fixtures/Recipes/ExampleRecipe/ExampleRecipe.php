<?php

namespace Tests\Fixtures\Recipes\ExampleRecipe;

use App\Recipes\Recipe;

class ExampleRecipe extends Recipe
{
    public function name(): string
    {
        return 'Example Recipe';
    }

    public function options(): array
    {
        return [];
    }

    public function commands(): array
    {
        return [];
    }

    protected function buildServices(): void
    {
    }
}
