<?php

namespace App\Docker\Services;

class MySql extends \App\Docker\Service
{

    protected function configure(): void
    {
        // TODO: Implement configure() method.
    }

    public function serviceName(): string
    {
        return "mysql";
    }
}
