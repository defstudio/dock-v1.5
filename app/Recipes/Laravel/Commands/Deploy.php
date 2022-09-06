<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature = 'laravel:deploy
                                {--hot : execute without using maintenance mode}';

    protected $description = 'Update Laravel codebase from git and run all deploy commands';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
