<?php

declare(strict_types=1);

namespace App\Docker;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class Network implements Arrayable
{
    protected bool $external = false;

    protected string $driver = 'bridge';

    public function __construct(protected string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function external(): static
    {
        $this->external = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->external) {
            return ['external' => true];
        }

        return [
            'name' => $this->name,
            'driver' => $this->driver,
        ];
    }
}
