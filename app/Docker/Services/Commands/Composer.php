<?php

namespace App\Docker\Services\Commands;

use App\Commands\Command;
use App\Concerns\ForwardsShellCommands;

class Composer extends Command
{
    use ForwardsShellCommands;

    protected $signature = 'composer';

    protected $description = 'Run a composer command';
}
