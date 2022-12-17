<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Recipes\Laravel\Services\Worker;

class RestartQueue extends Command
{
    protected $signature = 'queue:restart';

    protected $description = 'Restart queue workers';

    public function handle(): int
    {
        return $this->runInService(Worker::class, ['php', '/var/www/artisan', 'queue:restart']);
    }
}
