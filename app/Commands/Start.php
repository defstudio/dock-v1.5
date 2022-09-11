<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Services\RecipeService;
use Storage;

class Start extends Command
{
    protected $signature = 'start
                            {--build : rebuilds images before starting}
                            {--remove-orphans : remove orphans containers}';

    protected $description = 'Launch docker containers';

    private RecipeService $cookbook;

    public function handle(RecipeService $cookbook): int
    {
        $this->cookbook = $cookbook;

        return $this->tasks([
            'Ensuring src folder exists' => $this->ensureSrcFolderExists(...),
            'Generating docker-compose file' => $this->publishDockerComposeFile(...),
            'Publishing assets' => $this->publishAssets(...),
            'Starting containers' => $this->startContainers(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    private function ensureSrcFolderExists(): bool
    {
        if (!Storage::disk('cwd')->exists('src')) {
            return Storage::disk('cwd')->makeDirectory('src');
        }

        return true;
    }

    private function publishDockerComposeFile(): bool
    {
        return $this->cookbook->recipe()->publishDockerCompose();
    }

    private function publishAssets(): bool
    {
        return $this->cookbook->recipe()->publishAssets();
    }

    private function startContainers(): bool
    {
        $command = ['docker-compose', 'up', '-d'];

        if ($this->option('build')) {
            $command[] = '--build';
        }

        if ($this->option('remove-orphans')) {
            $command[] = '--remove-orphans';
        }

        $exitCode = $this->runInTerminal(
            $command,
            env: [
                'COMPOSE_DOCKER_CLI_BUILD' => 1,
                'DOCKER_BUILDKIT' => 1,
            ]
        );

        return $exitCode === 0;
    }
}
