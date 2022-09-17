<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can enter in shell for a running service', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake();

    restoreDefaultRecipes();

    $this->artisan('laravel:install')->assertSuccessful();

    Terminal::assertRan('docker-compose run --service-ports --rm composer composer create-project --prefer-dist laravel/laravel .');
});
