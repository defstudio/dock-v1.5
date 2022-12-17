<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Docker\Services\Composer;
use App\Docker\Services\Php;
use App\Enums\EnvKey;
use App\Facades\Env;
use App\Services\RecipeService;
use Storage;

class Install extends Command
{
    protected $signature = 'laravel:install';

    protected $description = 'Set up a new Laravel project';

    public function __construct(private readonly RecipeService $cookbook)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->title('Laravel installation');

        if (Env::get(EnvKey::git_enabled)) {
            return $this->cloneFromGitRepository() ? self::SUCCESS : self::FAILURE;
        }

        return $this->tasks([
            'Creating Laravel project' => $this->install(...),
            $this->init(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    private function cloneFromGitRepository(): bool
    {
        return $this->tasks([
            'Deploying code from '.Env::get(EnvKey::git_repository) => $this->deployFromGit(...),
            $this->init(...),
        ]);
    }

    private function deployFromGit(): bool
    {
        $repository = Env::get(EnvKey::git_repository);
        $branch = Env::get(EnvKey::git_branch);

        return $this->step("Checking out [$branch] branch", fn () => $this->runInShell([
            'cd', 'src',
            '&&', 'git', 'clone', '--branch', $branch,
            Env::production() ? '--single-branch' : '',
            $repository,
            '.',
        ]) === self::SUCCESS)
            && $this->step('Fixing files permissions', function () {
                $php = $this->cookbook->recipe()->getService(Php::class);

                return $this->runInTerminal(['chown', '-R', "{$php->getUserId()}:{$php->getUserId()}", 'src']) === self::SUCCESS;
            });
    }

    private function install(): bool
    {
        if ($this->runInService(Composer::class, ['composer', 'create-project', '--prefer-dist', 'laravel/laravel', '.']) !== self::SUCCESS) {
            return false;
        }

        Storage::disk('src')->delete('.env');

        return true;
    }

    private function init(): bool
    {
        return $this->call('laravel:init') === self::SUCCESS;
    }
}
