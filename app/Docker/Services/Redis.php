<?php

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Redis extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('redis');

        $version = $this->env('REDIS_VERSION', 7);

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'image' => "redis:$version-alpine",
            'command' => $this->buildCommand(),
            'expose' => [6379],
        ]);

        $this->addNetwork($this->internalNetworkName());

        if ($this->shouldPersistData()) {
            $this->addVolume('./volumes/redis/data', '/data');
        }
    }

    protected function shouldPersistData(): bool
    {
        return (bool) $this->env('REDIS_PERSIST_DATA');
    }

    protected function getPassword(): string
    {
        return $this->env('REDIS_PASSWORD', '');
    }

    protected function buildCommand(): string
    {
        return Str::of('redis-server')
            ->append(' --loglevel', ' ', $this->getLogLevel())
            ->when($this->shouldPersistData(), fn (Stringable $str) => $str->append(' --save', ' ', $this->getPersistenceSeconds(), ' ', $this->getPersistenceChangedKeys()))
            ->when($this->getPassword(), fn (Stringable $str, string $password) => $str->append(' --requirepass', ' ', $password))
            ->toString();
    }

    protected function getPersistenceSeconds(): int
    {
        return $this->env('REDIS_SNAPSHOT_EVERY_SECONDS', 60);
    }

    protected function getPersistenceChangedKeys(): int
    {
        return $this->env('REDIS_SNAPSHOT_EVERY_WRITES', 1);
    }

    protected function getLogLevel(): string
    {
        return 'warning';
    }
}
