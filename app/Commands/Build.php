<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Docker\Service;
use App\Facades\Terminal;
use App\Services\RecipeService;
use function Laravel\Prompts\select;

class Build extends Command
{
    protected $signature = 'build
                            {service? : service to build}';

    protected $description = 'Build a service';

    public function handle(RecipeService $cookbook): int
    {
        $availableServices = $cookbook->recipe()->services()->mapWithKeys(fn (Service $service, string $class) => [$service->name() => $class])->toArray();

        /** @var string $serviceName */
        $serviceName = $this->argument('service') ?? select(
            'Select a service',
            $availableServices,
        );

        $this->runInTerminal([
            'docker-compose',
            'pull',
            $serviceName,
        ]);

        $this->runInTerminal([
            'docker-compose',
            'up',
            '-d',
            '--no-deps',
            '--build',
            $serviceName,
        ], [
            'COMPOSE_DOCKER_CLI_BUILD' => 1,
            'DOCKER_BUILDKIT' => 1,
        ]);

        return self::SUCCESS;
    }
}
