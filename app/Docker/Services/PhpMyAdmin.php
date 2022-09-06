<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection LaravelFunctionsInspection */

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;

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
            'expose' => 80,
        ]);

        if (!empty($port = (int)env("PHPMYADMIN_PORT"))) {
            $this->mapPort($port, 80);
        }

        $this->addNetwork($this->internalNetworkName());
    }

    public function mysqlService(MySql $mysql): static{
        $this->serviceDefinition->set('environment.MYSQL_ROOT_PASSWORD', $mysql->getDatabaseRootPassword());
        $this->serviceDefinition->set('environment.PMA_HOST', $mysql->name());
        $this->dependsOn($mysql->name());
        return $this;
    }

    public function nginxService(Nginx $nginx): static{
        if(!empty($subdomain = env('PHPMYADMIN_SUBDOMAIN'))){
            $nginx->addSite(
                "$subdomain.{$this->host()}",
                80,
            )->proxy($this->name, 80);
        }

        return $this;
    }
}
