<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can stop all services', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake();

    restoreDefaultRecipes();

    $this->artisan('stop')->assertSuccessful();

    Terminal::assertRan('docker-compose down');
});
