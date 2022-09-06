<?php

/** @noinspection PhpUnused */

/** @noinspection LaravelFunctionsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Exceptions\DockerServiceException;
use Illuminate\Support\Str;

class Php extends Service
{
    protected float|string $version;

    protected array $allowedTargets = [
        'fpm', 'composer', 'scheduler', 'websocket', 'worker',
    ];

    protected function configure(): void
    {
        $this->setServiceName('php');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'working_dir' => '/var/www',
            'build' => [
                'context' => self::HOST_SERVICES_PATH."/$this->name",
                'target' => 'fpm',
            ],
            'expose' => [9000],
            'environment' => 'Dock',
        ]);

        if ($this->isDockerHostExposed()) {
            $this->serviceDefinition->push('extra_hosts', 'host.docker.internal:host-gateway');
        }

        if (env('REDIS_ENABLED')) {
            $this->dependsOn(app(Redis::class)->name());
        }

        if (env('DB_ENGINE') === 'mysql') {
            $this->dependsOn(app(MySql::class)->name());
        }

        $this->version(env('PHP_VERSION', 'latest'));

        $this->addVolume(self::HOST_SRC_PATH, $this->getWorkingDir());

        $this->addNetwork($this->internalNetworkName());
    }

    public function target(string $target): static
    {
        if (!in_array($target, $this->allowedTargets)) {
            throw DockerServiceException::generic("Unhallowed PHP target: [$target]");
        }

        $this->serviceDefinition->set('build.target', $target);

        return $this;
    }

    public function version(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    protected function isXdebugEnabled(): bool
    {
        if ($this->isProductionMode()) {
            return false;
        }

        return Str::of(env('EXTRA_TOOLS'))
            ->explode(',')
            ->each(fn (string $tool) => trim($tool))
            ->contains('xdebug');
    }

    protected function isLibreOfficeWriterEnabled(): bool
    {
        return Str::of(env('EXTRA_TOOLS'))
            ->explode(',')
            ->each(fn (string $tool) => trim($tool))
            ->contains('libreoffice_writer');
    }

    protected function isMySqlClientEnabled(): bool
    {
        return Str::of(env('EXTRA_TOOLS'))
            ->explode(',')
            ->each(fn (string $tool) => trim($tool))
            ->contains('mysql_client');
    }
}
