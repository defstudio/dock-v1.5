<?php

declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;
use App\Recipes\Laravel\Commands\RestartQueue;

it('can restart queues', function () {
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

    $this->artisan(RestartQueue::class)->assertSuccessful();

    Terminal::assertRan('docker-compose run --service-ports --rm worker php /var/www/artisan queue:restart');
});
