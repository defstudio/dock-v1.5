<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Tinker extends Command
{
    protected $signature = 'tinker';

    protected $description = 'Start Laravel tinker shell';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
