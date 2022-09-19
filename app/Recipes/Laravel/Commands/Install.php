<?php

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Docker\Services\Composer;
use Storage;

class Install extends Command
{
    protected $signature = 'laravel:install';

    protected $description = 'Set up a new Laravel project';

    public function handle(): int
    {
        return $this->tasks([
            'Laravel Installation' => $this->install(...),
            $this->setup(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    private function install(): bool
    {
        if ($this->runInService(Composer::class, ['composer', 'create-project', '--prefer-dist', 'laravel/laravel', '.']) !== self::SUCCESS) {
            return false;
        }

        Storage::disk('src')->delete('.env');

        return true;
    }

    private function setup(): bool
    {
        return $this->call('laravel:init') === self::SUCCESS;
    }
}
