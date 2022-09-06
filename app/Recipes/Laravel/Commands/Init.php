<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Init extends Command
{
    protected $signature = 'laravel:init';

    protected $description = 'Initialize an existing Laravel project';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
