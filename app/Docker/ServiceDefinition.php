<?php
declare(strict_types=1);

namespace App\Docker;

use Illuminate\Support\Arr;

class ServiceDefinition
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
}
