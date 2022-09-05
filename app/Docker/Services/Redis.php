<?php

namespace App\Docker\Services;

class Redis extends \App\Docker\Service
{

    protected function configure(): void
    {
        // TODO: Implement configure() method.
    }

    public function serviceName(): string
    {
        return "redis";
    }
}
