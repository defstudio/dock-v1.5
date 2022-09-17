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

    public function put(string|array $key, mixed $value = ''): static
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $item_key => $item_value) {
            $this->values[$item_key] = $item_value;
        }

        return $this;
    }

    public function dump(): void
    {
        dump($this->values);
    }
}
