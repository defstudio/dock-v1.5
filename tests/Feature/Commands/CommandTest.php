<?php

use App\Commands\Command;
use App\Facades\Terminal;

it('can run a command in terminal', function () {
    Terminal::fake();

    $command = new class extends Command {
    };

    $command->runInTerminal(['foo', 'bar', 'baz']);

    Terminal::assertRan(['foo', 'bar', 'baz']);
});
