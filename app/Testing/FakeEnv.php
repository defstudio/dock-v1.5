<?php

namespace App\Testing;

use App\Repositories\Env;

class FakeEnv extends Env
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(private array $values = [])
    {
    }

    public function exists(): bool
    {
        return !empty($this->values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value): static
    {
        $this->values[$key] = $value;

        return $this;
    }
}
