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

it('can run a command in terminal and return output', function () {
    Terminal::fake(commands: ['foo bar baz' => 'qux']);

    $command = new class extends Command
    {
    };

    $output = $command->runInTerminalAndReturnOutput(['foo', 'bar', 'baz']);

    expect($output)->toBe('qux');
});

it('can run a command in shell', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->runInShell(['foo', 'bar', 'baz']);

    Terminal::assertRanInShell(['foo', 'bar', 'baz']);
});

it('can run a command in shell and return output', function () {
    Terminal::fake(commands: ['foo bar baz' => 'qux']);

    $command = new class extends Command
    {
    };

    $output = $command->runInShellAndReturnOutput(['foo', 'bar', 'baz']);

    expect($output)->toBe('qux');
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

    Terminal::assertSentMessagesMatchSnapshot();
});

it('can render an error message', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->error('error foo');

    Terminal::assertSentMessagesMatchSnapshot();
});

it('can execute a step', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $result = $command->step('foo', fn () => true, 'red');

    expect($result)->toBeTrue();

    Terminal::assertSentMessagesMatchSnapshot();
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
        "\n",
        "  <bg=gray>one</>\n",
        'test',
        str_repeat('<fg=gray>.</>', max(min(terminal()->width(), 150) - 3 - 10, 0)),
        '<fg=gray>0ms</>',
        " <fg=green;options=bold>DONE</>\n",
        "\n",
        "  <bg=gray>two</>\n",
        str_repeat('<fg=gray>.</>', max(min(terminal()->width(), 150) - 3 - 10, 0)),
        '<fg=gray>0ms</>',
        " <fg=green;options=bold>DONE</>\n",
        "\n",
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

it('can render a title', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->title('foo');

    Terminal::assertSentMessagesMatchSnapshot();
});

it('can render a failure banner', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->failureBanner('foo');

    Terminal::assertSentMessagesMatchSnapshot();
});

it('can render a success banner', function () {
    Terminal::fake();

    $command = new class extends Command
    {
    };

    $command->successBanner('foo');

    Terminal::assertSentMessagesMatchSnapshot();
});
