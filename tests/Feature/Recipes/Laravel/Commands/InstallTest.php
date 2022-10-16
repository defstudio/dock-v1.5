<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;
use App\Recipes\Laravel\Commands\Install;
use App\Services\RecipeService;
use Illuminate\Console\Application;
use Illuminate\Foundation\Console\Kernel;


it('can run laravel installation', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake();
    restoreDefaultRecipes();


    $this->artisan(Install::class)->assertSuccessful();

    Terminal::assertRan('docker-compose run --service-ports --rm composer composer create-project --prefer-dist laravel/laravel .');
})->only();
