<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Docker\Services\Node;

class Vite extends Command
{
    protected $signature = 'vite';

    protected $description = 'Launch Vite server';

    public function handle(): int
    {
        return $this->runInService(Node::class, ['npm', 'run', 'dev']);
    }
}
