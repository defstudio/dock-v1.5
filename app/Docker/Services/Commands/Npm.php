<?php
declare(strict_types=1);

namespace App\Docker\Services\Commands;

use App\Commands\Command;
use App\Concerns\ForwardsShellCommands;
use App\Facades\Terminal;

class Npm extends Command
{
    use ForwardsShellCommands;

    protected $signature = 'npm';
    protected $description = 'Run an npm command';

    protected string $targetService = \App\Docker\Services\Node::class;
}
