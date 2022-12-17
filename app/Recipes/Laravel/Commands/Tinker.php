<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Docker\Services\Php;

class Tinker extends Command
{
    protected $signature = 'tinker';

    protected $description = 'Start Laravel tinker shell';

    public function handle(): int
    {
        return $this->runInService(Php::class, ['php', 'artisan', 'tinker']);
    }
}
