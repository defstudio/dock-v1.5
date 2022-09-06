<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Vite extends Command
{
    protected $signature = 'vite';

    protected $description = 'Launch Vite server';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
