<?php
declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can enter in shell for a running service', function(){
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake(commands: [
        'docker-compose ps php' => 'Up',
    ]);

    restoreDefaultRecipes();

    $this->artisan('shell php')->assertSuccessful();

    Terminal::assertRan("docker-compose exec php /bin/bash");
});

it('can enter in shell for a non-running service', function(){
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake(commands: [
        'docker-compose ps composer' => 'Exit',
    ]);

    restoreDefaultRecipes();

    $this->artisan('shell composer')->assertSuccessful();

    Terminal::assertRan("docker-compose run --service-ports --rm composer /bin/bash");
});
