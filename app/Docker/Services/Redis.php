<?php

/** @noinspection LaravelFunctionsInspection */

namespace App\Docker\Services;

use App\Docker\ServiceDefinition;

class Redis extends \App\Docker\Service
{
    protected function configure(): void
    {
        $this->setServiceName('redis');

        $version = env('REDIS_VERSION', 7);

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'image' => "redis:$version-alpine",
            'expose' => [6379],
        ]);

        $this->addNetwork($this->internalNetworkName());
    }
}
