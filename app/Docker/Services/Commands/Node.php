<?php

namespace App\Docker\Services\Commands;

use App\Commands\Command;
use App\Concerns\ForwardsShellCommands;

class Node extends Command
{
    use ForwardsShellCommands;

    protected $signature = 'node';

    protected $description = 'Run a node command';
}
