<?php

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Facades\Env;

class Redis extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('redis');

        $version = $this->env('REDIS_VERSION', 7);

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'image' => "redis:$version-alpine",
            'expose' => [6379],
        ]);

        $this->addNetwork($this->internalNetworkName());
    }
}
