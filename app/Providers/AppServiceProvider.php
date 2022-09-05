<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RecipeService;
use App\Termwind\Terminal;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('terminal', fn () => new Terminal());
    }
}
