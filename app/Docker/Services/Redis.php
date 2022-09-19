<?php

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Enums\EnvKey;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Redis extends Service
{
    protected function configure(): void
    {
        $this->setServiceName('redis');

        $version = $this->env(EnvKey::redis_version, 7);

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
        return (bool) $this->env(EnvKey::redis_persist_data);
    }

    protected function getPassword(): string
    {
        return $this->env(EnvKey::redis_password, '');
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
        return $this->env(EnvKey::redis_snapshot_every_seconds, 60);
    }

    protected function getPersistenceChangedKeys(): int
    {
        return $this->env(EnvKey::redis_snapshot_every_writes, 1);
    }

    protected function getLogLevel(): string
    {
        return 'warning';
    }
}
