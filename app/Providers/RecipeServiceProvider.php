<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace App\Providers;

use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class RecipeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(RecipeService::class, RecipeService::class);
    }

    public function boot(): void
    {
        if (($_SERVER['argv'][1] ?? '') === 'init') {
            return;
        }

        if (!Storage::disk('cwd')->exists('.env')) {
            return;
        }

        $cookbook = $this->app->make(RecipeService::class);
        $cookbook->recipe()->build();
        $this->commands($cookbook->recipe()->commands());
    }
}
