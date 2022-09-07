<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Services\RecipeService;

class Start extends Command
{
    protected $signature = 'start
                            {--build : rebuilds images before starting}
                            {--remove-orphans : remove orphans containers}';

    protected $description = 'Launch docker containers';

    public function handle(RecipeService $cookbook): int
    {

        $this->components->task('Generating docker-compose file', function () use ($cookbook) {
            return $cookbook->recipe()->publishDockerCompose();
        });

        return self::SUCCESS;
    }
}
