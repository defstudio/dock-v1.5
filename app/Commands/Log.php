<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Docker\Service;
use App\Facades\Terminal;
use App\Services\RecipeService;

class Log extends Command
{
    protected $signature = 'log
                            {service? : service to show ("all" to show all logs)}';

    protected $description = 'Show logs from a service';

    public function handle(RecipeService $cookbook): int
    {
        $availableServices = $cookbook->recipe()->services()->map(fn(Service $service) => $service->name())->prepend('all')->toArray();

        $service = $this->argument('service') ?? Terminal::choose('Select a service', $availableServices, 'all');

        $command = ['docker-compose', 'logs', '--follow', '--tail=50'];
        if ($service !== 'all') {
            $command[] = $service;
        }

        $this->runInTerminal($command);

        return self::SUCCESS;
    }
}
