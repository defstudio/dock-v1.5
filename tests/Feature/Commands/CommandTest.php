<?php

use App\Commands\Command;
use App\Facades\Env;
use App\Facades\Terminal;
use App\Services\RecipeService;

it('can run a command in terminal', function () {
    Terminal::fake();

    $command = new class extends Command {
    };

    $command->runInTerminal(['foo', 'bar', 'baz']);

    Terminal::assertRan(['foo', 'bar', 'baz']);
});

it('can run a command in a running service', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'foo.com']);
    restoreDefaultRecipes();

    Terminal::fake(commands: [
        'docker-compose ps php' => 'Up',
    ]);

    $command = new class extends Command{

    };

    $command->runInService('php', ['qux', 'quux']);

    Terminal::assertRan('docker-compose exec php qux quux');
});

it('can run a command in a non-running service', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'foo.com']);
    restoreDefaultRecipes();

    Terminal::fake(commands: [
        'docker-compose ps php' => 'Exit',
    ]);

    $command = new class extends Command{

    };

    $command->runInService('php', ['qux', 'quux']);

    Terminal::assertRan('docker-compose run --service-ports --rm php qux quux');
});

it('can render a warning message', function () {
    Terminal::fake();

    $command = new class extends Command{

    };

    $command->warn('warning foo');

    Terminal::assertSent('Warning warning foo');
});

it('can render an error message', function () {
    Terminal::fake();

    $command = new class extends Command{

    };

    $command->error('error foo');

    Terminal::assertSent('Error error foo');
});
