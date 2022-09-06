<?php

namespace App\Docker\Services\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Node extends Command
{
    protected $signature = 'node';

    protected $description = 'Run a node command';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
