<?php

declare(strict_types=1);

namespace App\Docker;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class ServiceDefinition implements Arrayable
{
    /**
     * @param  array<string, string|int|array<array-key, mixed>>  $config
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * @param  string|int|array<string, mixed>  $value
     */
    public function set(string $key, string|int|array $value): void
    {
        Arr::set($this->config, $key, $value);
    }

    /**
     * @param  array<string, mixed>|string|int|null  $default
     * @return array<string, mixed>|string|int|null
     */
    public function get(string $key, array|string|int $default = null): array|string|int|null
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param  string|int|array<string, mixed>  $value
     */
    public function push(string $key, string|int|array $value): void
    {
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
     * @return array<string, string|int|array<array-key, mixed>>
     */
    public function toArray(): array
    {
        return $this->config;
    }
}
