<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

class ShowServices extends Command
{
    protected $signature = 'show:services';

    protected $description = 'Show all project services';

    public function handle(): int
    {
        $this->runInTerminal(['docker-compose', 'ps']);

        return self::SUCCESS;
    }
}
