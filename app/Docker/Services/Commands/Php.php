<?php
declare(strict_types=1);

namespace App\Docker\Services\Commands;

use App\Commands\Command;
use App\Concerns\ForwardsShellCommands;
use App\Facades\Terminal;

class Php extends Command
{
    use ForwardsShellCommands;

    protected $signature = 'php';
    protected $description = 'Run an php command';
}
