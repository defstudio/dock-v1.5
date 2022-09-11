<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Services\RecipeService;

class Stop extends Command
{
    protected $signature = 'stop';

    protected $description = 'Stop all docker containers';

    public function handle(): int
    {
        return $this->tasks([
            'Stopping containers' => $this->stopContainers(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    private function stopContainers(): bool
    {
        $command = ['docker-compose', 'down'];

        $exitCode = $this->runInTerminal($command);

        return $exitCode === 0;
    }
}
