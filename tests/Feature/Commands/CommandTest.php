<?php

use App\Commands\Command;
use App\Docker\Services\Php;
use App\Facades\Env;
use App\Facades\Terminal;
use Illuminate\Console\OutputStyle;
use function Termwind\terminal;

it('can run a command in terminal', function () {
    Terminal::fake();

    $command = new class extends Command
    {
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

    $command = new class extends Command
    {
    };

    $command->runInService(Php::class, ['qux', 'quux']);

    Terminal::assertRan('docker-compose exec php qux quux');
});

it('can run a command in a non-running service', function () {
    Env::fake(['RECIPE' => 'laravel', 'HOST' => 'foo.com']);
    restoreDefaultRecipes();

    Terminal::fake(commands: [
        'docker-compose ps php' => 'Exit',
    ]);

    $command = new class extends Command
    {
    };

    $command->runInService(Php::class, ['qux', 'quux']);

    Terminal::assertRan('docker-compose run --service-ports --rm php qux quux');
});

it('can render a warning message', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->warn('warning foo');

    Terminal::assertSent('Warning warning foo');
});

it('can render an error message', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->error('error foo');

    Terminal::assertSent('Error error foo');
});

it('can execute a list of tasks', function () {
    $output = fakeConsoleRenderer();
    $command = new class extends Command
    {
    };

    $command->setOutput(new OutputStyle(App\Terminal\Terminal::getStreamableInput(), $output));

    $command->tasks([
        'one' => function () use ($command) {
            $command->write('test');

            return true;
        },
        'two' => fn () => true,
        'three' => fn () => false,
        'four' => fn () => true,
    ]);

    expect($output->output)->toBe([
        "  <bg=gray>one</>\n",
        'test',
        str_repeat('<fg=gray>.</>', max(min(terminal()->width(), 150) - 3 - 10, 0)),
        '<fg=gray>0ms</>',
        " <fg=green;options=bold>DONE</>\n",
        "  <bg=gray>two</>\n",
        str_repeat('<fg=gray>.</>', max(min(terminal()->width(), 150) - 3 - 10, 0)),
        '<fg=gray>0ms</>',
        " <fg=green;options=bold>DONE</>\n",
        "  <bg=gray>three</>\n",
        str_repeat('<fg=gray>.</>', max(min(terminal()->width(), 150) - 3 - 10, 0)),
        '<fg=gray>0ms</>',
        " <fg=red;options=bold>FAIL</>\n",
    ]);
});

it('can write a line', function () {
    $output = fakeConsoleRenderer();
    $command = new class extends Command
    {
    };

    $command->setOutput(new OutputStyle(App\Terminal\Terminal::getStreamableInput(), $output));

    $command->write('foo');
    $command->write('bar', true);
    $command->writeLn('baz');

    expect($output->output)->toBe([
        'foo',
        "bar\n",
        "baz\n",
    ]);
});
