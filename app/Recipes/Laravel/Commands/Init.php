<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes\Laravel\Commands;

use App\Commands\Command;
use App\Concerns\InteractsWithEnvContent;
use App\Docker\Services\Nginx;
use App\Docker\Site;
use App\Enums\EnvKey;
use App\Facades\Env;
use App\Services\RecipeService;
use Storage;

class Init extends Command
{
    use InteractsWithEnvContent;

    protected $signature = 'laravel:init';

    protected $description = 'Initialize an existing Laravel project';

    public function __construct(private RecipeService $cookbook)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return $this->tasks([
            'Ensuring .env file exists' => $this->ensureEnvFileExists(...),
        ]) ? self::SUCCESS : self::FAILURE;
    }

    public function ensureEnvFileExists(): bool
    {
        if (Storage::disk('src')->exists('.env')) {
            return true;
        }

        $this->step('Copying .env.example to .env');
        if (!Storage::disk('src')->copy('.env.example', '.env')) {
            $this->error('Both .env and .env.example are missing');

            return false;
        }

        $this->step('Updating .env file with dock settings');
        $this->compileEnvFile();

        return true;
    }

    private function compileEnvFile(): void
    {
        Env::put(EnvKey::env, 'local')
            ->put(EnvKey::db_engine, 'mysql');

        $content = Storage::disk('src')->get('.env');

        /** @var Site $nginxSite */
        $nginxSite = $this->cookbook->recipe()->getService(Nginx::class)->sites()->first();

        $this->setEnvValue($content, 'APP_URL', ($nginxSite->getPort() === 443 ? 'https://' : 'http://').Env::get(EnvKey::host));
        $this->setEnvValue($content, 'APP_ENV', Env::get(EnvKey::env));
        $this->setEnvValue($content, 'DB_HOST', $this->cookbook->recipe()->getDatabaseService()->name());

        Storage::disk('src')->put('.env', $content);
    }
}
