<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can run tinker', function () {
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

    $this->artisan(\App\Recipes\Laravel\Commands\Vite::class)->assertSuccessful();

    Terminal::assertRan('docker-compose run --service-ports --rm node npm run dev');
});
