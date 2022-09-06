<?php

namespace Tests\Fixtures\Recipes\TestRecipe;

use App\Recipes\Recipe;

class TestRecipe extends Recipe
{
    public function name(): string
    {
        return 'Test Recipe';
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
