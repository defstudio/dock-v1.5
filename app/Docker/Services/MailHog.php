<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Enums\EnvKey;

class MailHog extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('mailhog');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'expose' => [8025, 1025],
            'image' => 'mailhog/mailhog:latest',
        ]);

        if (!empty($port = (int) $this->env(EnvKey::mailhog_port))) {
            $this->mapPort($port, 8025);
        }

        $this->addNetwork($this->internalNetworkName());
    }

    public function nginxService(Nginx $nginx): static
    {
        if (!empty($subdomain = $this->env(EnvKey::mailhog_subdomain))) {
            $nginx->addSite("$subdomain.{$this->host()}",(int)$this->env(EnvKey::nginx_port, 80))
                ->proxy($this->name, 8025);
        }

        return $this;
    }
}
