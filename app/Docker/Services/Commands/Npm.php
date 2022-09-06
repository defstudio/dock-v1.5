<?php

namespace App\Docker\Services\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Npm extends Command
{
    protected $signature = 'npm';

    protected $description = 'Run an npm command';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
