<?php

namespace App\Docker\Services\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Composer extends Command
{
    protected $signature = 'composer';

    protected $description = 'Run a composer command';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
