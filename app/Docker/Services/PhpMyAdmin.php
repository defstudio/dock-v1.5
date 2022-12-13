<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Enums\EnvKey;

class PhpMyAdmin extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('phpmyadmin');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'image' => 'phpmyadmin/phpmyadmin',
            'environment' => [
                'UPLOAD_LIMIT' => 3000000000,
            ],
            'expose' => [80],
        ]);

        if (!empty($port = (int) $this->env(EnvKey::phpmyadmin_port))) {
            $this->mapPort($port, 80);
        }

        $this->addNetwork($this->internalNetworkName());
    }

    public function mysqlService(MySql $mysql): static
    {
        $this->serviceDefinition->set('environment.MYSQL_ROOT_PASSWORD', $mysql->getDatabaseRootPassword());
        $this->serviceDefinition->set('environment.PMA_HOST', $mysql->name());
        $this->dependsOn($mysql->name());

        return $this;
    }

    public function nginxService(Nginx $nginx): static
    {
        if (!empty($subdomain = $this->env(EnvKey::phpmyadmin_subdomain))) {
            $nginx->addSite("$subdomain.{$this->host()}", (int) $this->env(EnvKey::nginx_port, 80))
                ->proxy($this->name, 80);
        }

        return $this;
    }
}
