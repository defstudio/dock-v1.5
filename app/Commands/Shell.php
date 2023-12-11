<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Docker\Service;
use App\Facades\Terminal;
use App\Services\RecipeService;
use function Laravel\Prompts\select;

class Shell extends Command
{
    protected $signature = 'shell
                            {service? : service log in}';

    protected $description = 'Log into a service shell';

    public function handle(RecipeService $cookbook): int
    {
        $availableServices = $cookbook->recipe()->services()->mapWithKeys(fn (Service $service, string $class) => [$service->name() => $class])->toArray();

        $serviceName = $this->argument('service') ?? select('Select a service', $availableServices);

        $this->runInService($availableServices[$serviceName], ['/bin/bash']);

        return self::SUCCESS;
    }
}
