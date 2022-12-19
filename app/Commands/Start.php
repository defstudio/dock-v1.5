<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Services\RecipeService;
use Storage;

class Start extends Command
{
    protected $signature = 'start
                            {--build : rebuilds images before starting}';

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
            $result = Storage::disk('cwd')->makeDirectory('src');

            if (!$result) {
                return false;
            }
        }

        Storage::disk('cwd')->setVisibility('src', 'public');

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
        $command = ['docker-compose', 'up', '-d', '--remove-orphans'];

        if ($this->option('build')) {
            $command[] = '--build';
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
