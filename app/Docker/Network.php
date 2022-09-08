<?php

declare(strict_types=1);

namespace App\Docker;

class Network
{
    public function __construct(protected string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }
}
