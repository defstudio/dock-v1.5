<?php

declare(strict_types=1);

namespace App\Docker;

use Illuminate\Support\Arr;

class ServiceDefinition
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
     * @param  array<string, mixed>|null  $default
     * @return array<string, mixed>
     */
    public function getArray(string $key, array|null $default = null): array
    {
        return Arr::get($this->config, $key, $default);
    }

    public function get(string $key, string|int $default = null): string|int
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param  string|int|array<string, mixed>  $value
     */
    public function push(string $key, string|int|array $value): void
    {
        $value = collect($this->getArray($key, []))
            ->push($value)
            ->unique()
            ->toArray();

        $this->set($key, $value);
    }

    public function unset(string $key): void
    {
        Arr::forget($this->config, $key);
    }
}
