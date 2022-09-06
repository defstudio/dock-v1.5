<?php

namespace App\Docker\Services\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class NginxRestart extends Command
{
    protected $signature = 'nginx:restart';

    protected $description = 'Restart Nginx';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
