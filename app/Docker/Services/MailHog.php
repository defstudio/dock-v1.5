<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Facades\Env;

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

        if (!empty($port = (int) Env::get('MAILHOG_PORT'))) {
            $this->mapPort($port, 8025);
        }

        $this->addNetwork($this->internalNetworkName());
    }

    public function nginxService(Nginx $nginx): static
    {
        if (!empty($subdomain = Env::get('MAILHOG_SUBDOMAIN'))) {
            $nginx->addSite(
                "$subdomain.{$this->host()}",
                80,
            )->proxy($this->name, 8025);
        }

        return $this;
    }
}
