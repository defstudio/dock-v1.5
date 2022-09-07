<?php

namespace App\Docker;

class Volume
{
    public function __construct(protected string $hostPath, protected string $containerPath)
    {
    }

    public function hostPath(): string
    {
        return $this->hostPath;
    }

    public function containerPath(): string
    {
        return $this->containerPath;
    }
}
