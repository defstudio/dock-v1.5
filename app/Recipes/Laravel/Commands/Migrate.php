<?php

namespace App\Recipes\Laravel\Commands;

use App\Facades\Terminal;
use Illuminate\Console\Command;

class Migrate extends Command
{
    protected $signature = 'migrate';

    protected $description = 'Launch an Artisan Migration';

    public function handle(): int
    {
        Terminal::error('Coming soon');

        return self::FAILURE;
    }
}
