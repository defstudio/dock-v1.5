<?php

declare(strict_types=1);

namespace App\Docker;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

/**
 * @implements Arrayable<string, mixed>
 */
class ServiceDefinition implements Arrayable
{
    public function __construct(protected array $config)
    {
    }

    public function set(string $key, mixed $value): void
    {
        Arr::set($this->config, $key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function push(string $key, mixed $value): void
    {
        /* @phpstan-ignore-next-line  */
        $value = collect($this->get($key, []))
            ->push($value)
            ->unique()
            ->toArray();

        $this->set($key, $value);
    }

    public function unset(string $key): void
    {
        Arr::forget($this->config, $key);
    }

    /**
     * @return array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->config;
    }
}
