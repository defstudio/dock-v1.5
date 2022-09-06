<?php /** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
/** @noinspection LaravelFunctionsInspection */

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;

class MySql extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('mysql');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'command' => '--character-set-server=utf8 --collation-server=utf8_general_ci --default-authentication-plugin=mysql_native_password',
            'image' => 'mysql:8',
            'cap_add' => [
                'SYS_NICE',
            ],
            'expose' => [3306],
        ]);

        $this->setDatabaseName((string) env('MYSQL_DATABASE', 'database'));
        $this->setDatabaseUser((string) env('MYSQL_USER', 'dbuser'));
        $this->setDatabasePassword((string) env('MYSQL_PASSWORD', 'dbpassword'));
        $this->setDatabaseRootPassword((string) env('MYSQL_ROOT_PASSWORD', 'root'));

        if (! ! env('MYSQL_DISABLE_STRICT_MODE')) {
            $this->disableStrictMode();
        }

        if(!empty($port = (int)env('MYSQL_PORT'))){
            $this->mapPort($port, 3306);
        }

        $this->addVolume('./volumes/mysql/db', '/var/lib/mysql');

        $this->addNetwork($this->internalNetworkName());
    }

    public function setDatabaseName(string $name): static
    {
        $this->serviceDefinition->set('environment.MYSQL_DATABASE', $name);
        return $this;
    }

    public function getDatabaseName(): string
    {
        return $this->serviceDefinition->get('environment.MYSQL_DATABASE');
    }

    public function setDatabaseUser(string $name): static
    {
        $this->serviceDefinition->set('environment.MYSQL_USER', $name);
        return $this;
    }

    public function getDatabaseUser(): string
    {
        return $this->serviceDefinition->get('environment.MYSQL_USER');
    }

    public function setDatabasePassword(string $value): static
    {
        $this->serviceDefinition->set('environment.MYSQL_PASSWORD', $value);
        return $this;
    }

    public function getDatabasePassword(): string
    {
        return $this->serviceDefinition->get('environment.MYSQL_PASSWORD');
    }

    public function setDatabaseRootPassword(string $value): static
    {
        $this->serviceDefinition->set('environment.MYSQL_ROOT_PASSWORD', $value);
        return $this;
    }

    public function getDatabaseRootPassword(): string
    {
        return $this->serviceDefinition->get('environment.MYSQL_ROOT_PASSWORD');
    }

    public function disableStrictMode(): static
    {
        $command = $this->serviceDefinition->get('command');
        $command .= ' --sql_mode=""';

        $this->serviceDefinition->set('command', $command);

        return $this;
    }
}
