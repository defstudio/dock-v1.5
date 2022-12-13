<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Concerns\InteractsWithEnvContent;
use App\Docker\Services\Composer;
use App\Docker\Services\MailHog;
use App\Docker\Services\Nginx;
use App\Docker\Services\Node;
use App\Docker\Services\Php;
use App\Docker\Services\Redis;
use App\Docker\Site;
use App\Enums\EnvKey;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Worker;
use App\Services\RecipeService;
use Storage;

class Init extends Command
{
    use InteractsWithEnvContent;

    protected $signature = 'laravel:init';

    protected $description = 'Initialize an existing Laravel project';

    public function __construct(protected readonly RecipeService $cookbook)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->title('Laravel initialization');

        $success = $this->executeInit();

        if (!$success) {
            $this->failureBanner('Application initialization failed');

            return self::FAILURE;
        }

        $this->successBanner('Your Laravel application in configured and ready to use');

        return self::SUCCESS;
    }

    protected function executeInit(): bool
    {
        return $this->tasks([
            'Ensuring .env file exists' => $this->ensureEnvFileExists(...),
            'Installing composer packages' => $this->installComposerPackages(...),
            'Installing npm packages' => $this->installNpmPackages(...),
            'Building assets' => $this->buildAssets(...),
            'Database setup' => $this->setupDatabase(...),
            'Checking APP_KEY' => $this->ensureAppKeyExists(...),
            Env::production() ? 'Refreshing cache' : 'Cleaning cache' => $this->refreshCache(...),
            'Restarting queues' => $this->restartQueues(...),
        ]);
    }

    private function ensureEnvFileExists(): bool
    {
        if (!Storage::disk('src')->exists('.env')) {
            $this->step('Copying .env.example to .env');
            if (!Storage::disk('src')->copy('.env.example', '.env')) {
                $this->error('Both .env and .env.example are missing');

                return false;
            }
        }

        $this->step('Updating .env file with dock settings');
        $this->compileEnvFile();

        return true;
    }

    private function compileEnvFile(): void
    {
        $content = Storage::disk('src')->get('.env');

        /** @var Site $nginxSite */
        $nginxSite = $this->cookbook->recipe()->getService(Nginx::class)->sites()->first();

        $this->setEnvValue($content, 'APP_URL', ($nginxSite->getPort() === 443 ? 'https://' : 'http://').Env::get(EnvKey::host));

        $this->setEnvValue($content, 'APP_ENV', Env::get(EnvKey::env));

        $this->setEnvValue($content, 'DB_CONNECTION', $this->cookbook->recipe()->getDatabaseService()->name());
        $this->setEnvValue($content, 'DB_HOST', $this->cookbook->recipe()->getDatabaseService()->name());
        $this->setEnvValue($content, 'DB_DATABASE', Env::get(EnvKey::db_name));
        $this->setEnvValue($content, 'DB_PORT', 3306);
        $this->setEnvValue($content, 'DB_USERNAME', Env::get(EnvKey::db_user));
        $this->setEnvValue($content, 'DB_PASSWORD', Env::get(EnvKey::db_password));

        if (Env::get(EnvKey::redis_enabled)) {
            $this->setEnvValue($content, 'REDIS_HOST', $this->cookbook->recipe()->getService(Redis::class)->name());
            $this->setEnvValue($content, 'REDIS_PORT', 6379);
            $this->setEnvValue($content, 'REDIS_PASSWORD', Env::get(EnvKey::redis_password, 'null'));
        }

        if (Env::get(EnvKey::mailhog_enabled)) {
            $this->setEnvValue($content, 'MAIL_MAILER', 'smtp');
            $this->setEnvValue($content, 'MAIL_HOST', $this->cookbook->recipe()->getService(MailHog::class)->name());
            $this->setEnvValue($content, 'MAIL_PORT', 1025);
            $this->setEnvValue($content, 'MAIL_USERNAME', 'null');
            $this->setEnvValue($content, 'MAIL_PASSWORD', 'null');
            $this->setEnvValue($content, 'MAIL_ENCRYPTION', 'null');
        }

        Storage::disk('src')->put('.env', $content);
    }

    private function installComposerPackages(): bool
    {
        $command = ['composer', 'install'];

        if (Env::production()) {
            $command[] = '--no-dev';
            $command[] = '--optimize-autoloader';
        }

        return $this->runInService(Composer::class, $command) === self::SUCCESS;
    }

    private function installNpmPackages(): bool
    {
        $command = ['npm', 'install'];

        if (Env::production()) {
            $command[] = '--production';
        }

        return $this->runInService(Node::class, $command) === self::SUCCESS;
    }

    private function buildAssets(): bool
    {
        if (Env::production()) {
            $command = ['npm', 'run', 'prod'];
        } else {
            if (Storage::disk('src')->exists('vite.config.js')) {
                $this->info('Skipped building assets: this Laravel installation relies on Vite for serving assets during development');

                return true;
            }
            $command = ['npm', 'run', 'dev'];
        }

        return $this->runInService(Node::class, $command) === self::SUCCESS;
    }

    private function setupDatabase(): bool
    {
        $this->step('Running Migrations');

        if ($this->runInService(Php::class, ['php', 'artisan', 'migrate', '--force']) !== self::SUCCESS) {
            return false;
        }

        $this->step('Running Seeders');

        return $this->runInService(Php::class, ['php', 'artisan', 'db:seed', '--force']) === self::SUCCESS;
    }

    private function ensureAppKeyExists(): bool
    {
        $envContent = Storage::disk('src')->get('.env') ?? '';

        if (empty($this->getEnvValue($envContent, 'APP_KEY'))) {
            $this->step('Generating a new APP_KEY');

            return $this->runInService(Php::class, ['php', 'artisan', 'key:generate']) === self::SUCCESS;
        }

        return true;
    }

    private function refreshCache(): bool
    {
        $cacheOperation = Env::production() ? 'cache' : 'clear';

        return $this->step('Configuration cache', fn () => $this->runInService(Php::class, ['php', 'artisan', "config:$cacheOperation"]) === self::SUCCESS)
            && $this->step('Route cache', fn () => $this->runInService(Php::class, ['php', 'artisan', "route:$cacheOperation"]) === self::SUCCESS)
            && $this->step('View cache', fn () => $this->runInService(Php::class, ['php', 'artisan', "view:$cacheOperation"]) === self::SUCCESS);
    }

    private function restartQueues(): bool
    {
        return $this->runInService(Worker::class, ['php', '/var/www/artisan', 'queue:restart']) === self::SUCCESS;
    }
}
