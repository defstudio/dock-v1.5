<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can lanch an artisan command', function () {
    Env::fake([
        'RECIPE' => 'laravel',
        'HOST' => 'test.it',
        'ENV' => 'production',
        'DB_ENGINE' => 'mysql',
        'DB_DATABASE' => 'database',
        'DB_USER' => 'user',
        'DB_PASSWORD' => 'passwd',
    ]);
    Storage::fake('cwd');
    Terminal::fake();
    restoreDefaultRecipes();

    $this->artisan(\App\Recipes\Laravel\Commands\Artisan::class)->assertSuccessful();
    $this->artisan(\App\Recipes\Laravel\Commands\Artisan::class, ['inspire'])->assertSuccessful();
    $this->artisan(\App\Recipes\Laravel\Commands\Artisan::class, ['inspire', '--option', '--option2=value', 'value'])->assertSuccessful();

    Terminal::assertRan('docker-compose run --service-ports --rm php php artisan');
    Terminal::assertRan('docker-compose run --service-ports --rm php php artisan inspire');
    Terminal::assertRan('docker-compose run --service-ports --rm php php artisan inspire --option --option2=value value');
});
