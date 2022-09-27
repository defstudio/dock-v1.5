<?php

namespace App\Docker\Services\Commands;

use App\Commands\Command;
use App\Docker\Services\Nginx;

class NginxRestart extends Command
{
    protected $signature = 'nginx:restart';

    protected $description = 'Restart Nginx';

    public function handle(): int
    {
        $this->runInService(Nginx::class, ['service', 'nginx', 'restart']);

        return self::SUCCESS;
    }
}
