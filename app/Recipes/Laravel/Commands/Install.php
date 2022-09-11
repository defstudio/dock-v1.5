<?php

namespace App\Recipes\Laravel\Commands;


use App\Commands\Command;

class Install extends Command
{
    protected $signature = 'laravel:install';

    protected $description = 'Set up a new Laravel project';

    public function handle(): int
    {
        return $this->tasks([
            'Laravel Installation' => $this->install(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    private function install(): bool
    {
        $exitCode = $this->runInService(
            'composer',
            ['composer', 'create-project', '--prefer-dist', 'laravel/laravel', '.']
        );

        return $exitCode === 0;
    }
}
