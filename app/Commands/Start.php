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

        $this->components->task('Publishing assets', function () use ($cookbook) {
            return $cookbook->recipe()->publishAssets();
        });

        $this->components->task('Starting containers', function () use ($cookbook) {
            $command = ['docker-compose', 'up', '-d'];

            if($this->option('build')){
                $command[] = '--build';
            }

            if ($this->option('remove-orphans')) {
                $command[] = '--remove-orphans';
            }

            $exit_code = $this->runInTerminal(
                $command,
                environment_variables: [
                    'COMPOSE_DOCKER_CLI_BUILD' => 1,
                    'DOCKER_BUILDKIT' => 1,
                ]
            );

            if ($exit_code > 0) {
                return false;
            }

            return true;
        });

        return self::SUCCESS;
    }
}
