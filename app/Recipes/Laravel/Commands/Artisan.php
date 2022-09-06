<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Artisan extends Command
{
    protected $signature = 'artisan';

    protected $description = 'Run an Artisan command';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
