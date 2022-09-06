<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes\Laravel\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Docker\Services\Nginx;

class Dusk extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('dusk');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'working_dir' => '/var/www',
            'environment' => [
                'JAVA_OPTS' => '-Dwebdriver.chrome.whitelistedIps=',
            ],
            'image' => 'selenium/standalone-chrome',
        ]);

        $this->addVolume(self::HOST_SRC_PATH, '/var/www');

        $this->addNetwork($this->internalNetworkName());
    }

    public function nginxService(Nginx $nginx): static
    {
        $this->serviceDefinition->push('links', "$nginx->name:{$this->host()}");

        return $this;
    }
}
