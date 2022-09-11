<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Env;
use App\Terminal\Terminal;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('terminal', fn () => new Terminal());
        $this->app->bind('env_repository', fn () => new Env());
    }
}
