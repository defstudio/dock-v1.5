<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Commands;

use App\Docker\Services\Php;
use App\Enums\EnvKey;
use App\Facades\Env;
use Illuminate\Support\Collection;
use Storage;

class Deploy extends Init
{
    protected $signature = 'laravel:deploy
                                {--hot : execute without using maintenance mode}';

    protected $description = 'Update Laravel codebase from git and run all deploy commands';

    public function handle(): int
    {
        if (!Storage::disk('src')->exists('.git')) {
            $this->failureBanner("A git repository shold be present in 'src/' directory in order to start a deployment");

            return self::INVALID;
        }

        $this->title('Starting Laravel Deploy');

        $tasks = Collection::empty()
            ->when(!$this->option('hot'), fn (Collection $collection) => $collection->put('Going in maintenance mode', $this->goInMaintenanceMode(...)))
            ->put('Updating codebase from git', $this->pullCode(...))
            ->push($this->executeInit(...))
            ->when(!$this->option('hot'), fn (Collection $collection) => $collection->put('Going Live', $this->goLive(...)))
            ->toArray();

        $success = $this->tasks($tasks);

        if (!$success) {
            $this->failureBanner('Deployment failed');

            return self::FAILURE;
        }

        $this->successBanner('Deployment completed');

        return self::SUCCESS;
    }

    private function goInMaintenanceMode(): bool
    {
        if (Storage::disk('src')->exists('storage/framework/down')) {
            return true;
        }

        return $this->runInService(Php::class, [
            'php',
            'artisan',
            'down',
            '--refresh=15',
        ]) === self::SUCCESS;
    }

    private function goLive(): bool
    {
        return $this->runInService(Php::class, [
            'php',
            'artisan',
            'up',
        ]) === self::SUCCESS;
    }

    private function pullCode(): bool
    {
        $repository = Env::get(EnvKey::git_repository);
        $branch = Env::get(EnvKey::git_branch);

        $currentRepository = trim($this->runInShellAndReturnOutput(['cd', 'src', '&&', 'git config --get remote.origin.url']));

        if ($currentRepository !== $repository && !$this->step("Switch remote repository to $repository", fn () => $this->runInShell(['cd', 'src', '&&', 'git', 'remote', 'set-url', 'origin', $repository]) == self::SUCCESS)) {
            return false;
        }

        return $this->step('Resetting current branch', fn () => $this->runInShell([
            'cd', 'src',
            '&&', 'git', 'reset', '--hard',
        ]) === self::SUCCESS)
            && $this->step("Checking out last version from [$branch]", fn () => $this->runInShell([
                'cd', 'src',
                '&&', 'git', 'checkout', $branch,
                '&&', 'git', 'pull',
            ]) === self::SUCCESS)
            && $this->step('Checking out last version', fn () => $this->runInShell([
                'cd', 'src',
                '&&', 'git', 'reset', '--hard',
                '&&', 'git', 'checkout', $branch,
                '&&', 'git', 'pull',
            ]) === self::SUCCESS)
            && $this->step('Fixing files permissions', function () {
                $php = $this->cookbook->recipe()->getService(Php::class);

                return $this->runInTerminal(['chown', '-R', "{$php->getUserId()}:{$php->getUserId()}", 'src']) === self::SUCCESS;
            });
    }
}
