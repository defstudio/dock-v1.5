<?php

namespace App\Testing;

use App\Enums\EnvKey;
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

    public function get(EnvKey $key, mixed $default = null): mixed
    {
        return $this->values[$key->value] ?? $default;
    }

    public function put(EnvKey $key, mixed $value): static
    {
        $this->values[$key->value] = $value;

        return $this;
    }

    public function dump(): void
    {
        dump($this->values);
    }
}
