<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Docker\Services\Php;

class Migrate extends Command
{
    protected $signature = 'migrate';

    protected $description = 'Launch an Artisan Migration';

    public function handle(): int
    {
        return $this->runInService(Php::class, ['php', 'artisan', 'migrate']);
    }
}
