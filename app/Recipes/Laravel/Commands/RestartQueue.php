<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class RestartQueue extends Command
{
    protected $signature = 'queue:restart';

    protected $description = 'Restart queue workers';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
