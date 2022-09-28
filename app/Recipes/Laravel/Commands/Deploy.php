<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;

class Deploy extends Command
{
    protected $signature = 'laravel:deploy
                                {--hot : execute without using maintenance mode}';

    protected $description = 'Update Laravel codebase from git and run all deploy commands';

    public function handle(): int
    {
        $this->title("Starting Laravel Deploy");

        return $this->tasks([

        ]) ? self::SUCCESS : self::FAILURE;
    }
}
