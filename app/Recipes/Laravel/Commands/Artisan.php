<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Concerns\ForwardsShellCommands;
use App\Docker\Services\Php;

class Artisan extends Command
{
    use ForwardsShellCommands;

    protected $signature = 'artisan';

    protected $description = 'Run an Artisan command';

    protected string $command = 'php artisan';

    protected string $targetService = Php::class;
}
