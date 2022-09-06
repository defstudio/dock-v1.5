<?php

/** @noinspection LaravelFunctionsInspection */
declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Docker\Services\Commands\Npm;

class Node extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('node');

        $this->serviceDefinition = new ServiceDefinition([
            'working_dir' => '/var/www',
            'build' => [
                'context' => self::HOST_SERVICES_PATH."/$this->name",
            ],
        ]);

        $this->version(env('NODE_VERSION', 'lts'));

        if (! $this->isProductionMode()) {
            $this->mapPort(5173); //Vite port
        }

        $this->addVolume(self::HOST_SRC_PATH, '/var/www');

        $this->addNetwork($this->internalNetworkName());
    }

    public function version(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function commands(): array
    {
        return [
            Commands\Node::class,
            Npm::class,
        ];
    }
}
