<?php

namespace App\Providers;

use App\Recipes\Recipe;
use App\Services\RecipeService;
use App\Termwind\Terminal;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register(): void
    {
        $this->app->singleton(RecipeService::class, RecipeService::class);
        $this->app->bind('terminal', fn() => new Terminal());
    }
}
