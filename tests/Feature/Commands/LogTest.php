<?php
declare(strict_types=1);

use App\Facades\Env;
use App\Facades\Terminal;

it('can show logs for all services', function(){
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake();

    restoreDefaultRecipes();

    $this->artisan('log all')->assertSuccessful();

    Terminal::assertRan("docker-compose logs --follow --tail=50");
});


it('can show logs for specific service', function(){
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'test.it']);
    Storage::fake('cwd');
    Terminal::fake();

    restoreDefaultRecipes();

    $this->artisan('log php')->assertSuccessful();

    Terminal::assertRan("docker-compose logs --follow --tail=50 php");
});
