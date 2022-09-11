<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Facades\Terminal;
use App\Services\RecipeService;

class Shell extends Command
{
    protected $signature = 'shell
                            {service? : service log in}';

    protected $description = 'Log into a service shell';

    public function handle(RecipeService $cookbook): int
    {
        $availableServices = $cookbook->recipe()->services()->keys()->toArray();

        $service = $this->argument('service') ?? Terminal::choose('Select a service', $availableServices);

        $this->runInService($service, ['/bin/bash']);

        return self::SUCCESS;
    }
}
