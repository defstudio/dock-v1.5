<?php

namespace App\Docker;

class Volume
{
    public function __construct(protected string $hostPath, protected string $containerPath)
    {
    }

}
