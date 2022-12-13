<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes\Laravel;

use App\Docker\Service;
use App\Docker\Services\Composer;
use App\Docker\Services\MailHog;
use App\Docker\Services\MySql;
use App\Docker\Services\Nginx;
use App\Docker\Services\Node;
use App\Docker\Services\Php;
use App\Docker\Services\PhpMyAdmin;
use App\Docker\Services\Redis;
use App\Enums\DbEngine;
use App\Enums\EnvKey;
use App\Facades\Env;
use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;
use App\Recipes\ConfigurationSection;
use App\Recipes\Laravel\Commands\Artisan;
use App\Recipes\Laravel\Commands\Deploy;
use App\Recipes\Laravel\Commands\Init;
use App\Recipes\Laravel\Commands\Install;
use App\Recipes\Laravel\Commands\Migrate;
use App\Recipes\Laravel\Commands\RestartQueue;
use App\Recipes\Laravel\Commands\Tinker;
use App\Recipes\Laravel\Commands\Vite;
use App\Recipes\Laravel\Services\Dusk;
use App\Recipes\Laravel\Services\Scheduler;
use App\Recipes\Laravel\Services\Websocket;
use App\Recipes\Laravel\Services\Worker;
use App\Recipes\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Laravel extends Recipe
{
    public function name(): string
    {
        return 'Laravel';
    }

    /**
     * @return ConfigurationSection[]
     */
    public function options(): array
    {
        return [
            ConfigurationSection::make('General', [
                ConfigurationOption::make(EnvKey::recipe, $this->slug()),
                ConfigurationOption::make(EnvKey::host)
                    ->description('Host exposed to external environments')
                    ->question('Application hostname')
                    ->default('laravel.ktm'),
                ConfigurationOption::make(EnvKey::env)
                    ->description('Application environment')
                    ->choices(['local', 'production'])
                    ->default('local'),
                ConfigurationOption::make(EnvKey::php_version)
                    ->description('PHP Version')
                    ->default('latest'),
                ConfigurationOption::make(EnvKey::node_version)
                    ->description('Node Version')
                    ->default('lts'),
                ConfigurationOption::make(EnvKey::expose_docker_host)
                    ->description('Should Docker Host be exposed to containers (Docker > v20.04 only)?')
                    ->confirm()
                    ->default(false),
                ConfigurationOption::make(EnvKey::behind_proxy)
                    ->question('Is the application behind a reverse proxy?')
                    ->afterSet(function (string|int|bool $set, Configuration $configuration) {
                        if ($set) {
                            $configuration->set(EnvKey::reverse_proxy_network, 'reverse_proxy_network');
                        }
                    })
                    ->confirm()
                    ->default(false)
                    ->hidden(),
                ConfigurationOption::make(EnvKey::extra_tools)
                    ->description('Extra tools to be installed')
                    ->question('Install any extra tools?')
                    ->choices(function (Configuration $configuration) {
                        $tools = ['mysql_client', 'libreoffice_writer'];

                        if ($configuration->get(EnvKey::env) === 'production') {
                            return $tools;
                        }

                        if (app(Php::class, ['customEnv' => $configuration->toArray()])->isXdebugAvailable()) {
                            $tools[] = 'xdebug';
                        }

                        if (app(Php::class, ['customEnv' => $configuration->toArray()])->isPcovAvailable()) {
                            $tools[] = 'pcov';
                        }

                        $tools[] = 'browser_tests';

                        return $tools;
                    }, true)
                    ->optional(true),
            ]),
            ConfigurationSection::make('Services', [
                ConfigurationOption::make(EnvKey::db_engine)
                    ->question('Which database engine should be used?')
                    ->choices(DbEngine::cases())
                    ->default('mysql'),

                ConfigurationOption::make(EnvKey::mailhog_enabled)
                    ->question('Should MailHog be enabled?')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::env) !== 'production'),
                ConfigurationOption::make(EnvKey::websocket_enabled)
                    ->question('Should Websocket server be enabled?')
                    ->confirm()
                    ->default('no'),
                ConfigurationOption::make(EnvKey::redis_enabled)
                    ->question('Should Redis be enabled?')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => !Str::of((string) $configuration->get(EnvKey::php_version))->startsWith('5.')),
            ]),

            ConfigurationSection::make('Network Configuration', [
                ConfigurationOption::make(EnvKey::nginx_port)
                    ->question('Enter nginx exposed port')
                    ->default(80)
                    ->validate(function ($value) {
                        return is_numeric($value);
                    })
                    ->when(fn (Configuration $configuration) => !$configuration->get(EnvKey::behind_proxy)),

                ConfigurationOption::make(EnvKey::nginx_external_certificate)
                    ->question("Do you want to set up a custom ssl certificate?
                                        <div class='ml-2'>This setup will allow you to define an external folder to load ssl certificates into nginx setup</div>
                                        <div class='ml-2'>Note: the folder must contain at least the following files:</div>
                                        <div class='ml-2'>- live/[hostname]/fullchain.pem</div>
                                        <div class='ml-2'>- live/[hostname]/privkey.pem</div>
                                        <div class='ml-2'>Do you want to proceed?")
                    ->confirm()
                    ->default(false)
                    ->hidden()
                    ->when(fn (Configuration $configuration) => (!$configuration->get(EnvKey::behind_proxy) && $configuration->get(EnvKey::nginx_port) == 443) || $configuration->get(EnvKey::behind_proxy)),
                ConfigurationOption::make(EnvKey::nginx_external_certificate_folder)
                    ->question('Enter the path to the ssl certificates folder (absolute or relative to dock folder)')
                    ->validate(function (string|int|bool $path): string|bool {
                        $path = "$path";

                        $exists = Str::of($path)->startsWith(DIRECTORY_SEPARATOR)
                            ? File::exists($path)
                            : Storage::disk('cwd')->exists($path);

                        if (!$exists) {
                            return 'Invalid path';
                        }

                        return true;
                    })
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::nginx_external_certificate)),
                ConfigurationOption::make(EnvKey::nginx_external_certificate_hostname)
                    ->question('Enter the hostname contained in the certificate')
                    ->default(fn (Configuration $configuration) => $configuration->get(EnvKey::host))
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::nginx_external_certificate)),

            ]),

            ConfigurationSection::make('Database Configuration', [
                ConfigurationOption::make(EnvKey::db_port)
                    ->question('Enter mysql exposed port')
                    ->default(3306)
                    ->validate(fn ($value) => is_numeric($value))
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql')
                    ->optional(),

                ConfigurationOption::make(EnvKey::db_name)
                    ->description('Database name')
                    ->default('database')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql'),
                ConfigurationOption::make(EnvKey::db_user)
                    ->description('Database user')
                    ->default('dbuser')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql'),
                ConfigurationOption::make(EnvKey::db_password)
                    ->description('Database password')
                    ->default('dbpassword')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql'),
                ConfigurationOption::make(EnvKey::db_root_password)
                    ->description('Database root password')
                    ->default('root')
                    ->validate(fn (string|int|bool $value, Configuration $configuration) => $value === 'root' && $configuration->get(EnvKey::env) === 'production'
                        ? "you should not use 'root' in production environments"
                        : true
                    )
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql'),
                ConfigurationOption::make(EnvKey::db_disable_strict_mode)
                    ->description('Disable strict mode')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql'),

                ConfigurationOption::make(EnvKey::phpmyadmin_enabled)
                    ->question('Should PHPMyAdmin be enabled?')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql' && $configuration->get(EnvKey::env) !== 'production'),
                ConfigurationOption::make(EnvKey::phpmyadmin_port)
                    ->question('Enter PHPMyAdmin exposed port')
                    ->default(8081)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql' && $configuration->get(EnvKey::phpmyadmin_enabled) && !$configuration->get(EnvKey::behind_proxy)),
                ConfigurationOption::make(EnvKey::phpmyadmin_subdomain)
                    ->question('Enter PHPMyAdmin exposed subdomain')
                    ->default('db')
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::db_engine) === 'mysql' && $configuration->get(EnvKey::phpmyadmin_enabled)),
            ]),

            ConfigurationSection::make('MailHog Configuration', [
                ConfigurationOption::make(EnvKey::mailhog_port)
                    ->question('Enter MailHog exposed port')
                    ->default(8025)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::mailhog_enabled) && !$configuration->get(EnvKey::behind_proxy)),
                ConfigurationOption::make(EnvKey::mailhog_subdomain)
                    ->question('Enter MailHog exposed subdomain')
                    ->default('mail')
                    ->optional()
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::mailhog_enabled)),
            ]),

            ConfigurationSection::make('Websocket Configuration', [
                ConfigurationOption::make(EnvKey::websocket_port)
                    ->question('Enter Websocket server exposed port')
                    ->default(6001)
                    ->validate(fn ($value) => is_numeric($value))
                    ->when(fn (Configuration $configuration) => $configuration->get(EnvKey::websocket_enabled) && !$configuration->get(EnvKey::behind_proxy)),
            ]),

            ConfigurationSection::make('Redis Configuration', [
                ConfigurationOption::make(EnvKey::redis_version)
                    ->description('Redis Version')
                    ->choices(['5', '6', '7'])
                    ->default('7')
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::redis_enabled)),
                ConfigurationOption::make(EnvKey::redis_password)
                    ->description('Redis password (leave blank to disable)')
                    ->optional()
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::redis_enabled)),
                ConfigurationOption::make(EnvKey::redis_persist_data)
                    ->description('Should Redis data be persisted between container reboots?')
                    ->confirm()
                    ->default('yes')
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::redis_enabled)),
                ConfigurationOption::make(EnvKey::redis_snapshot_every_seconds)
                    ->description('After how many seconds should Redis dump the snapshot?')
                    ->default(60)
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::redis_persist_data)),
                ConfigurationOption::make(EnvKey::redis_snapshot_every_writes)
                    ->description('How many key changes are required to dump the snapshot?')
                    ->default(1)
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::redis_persist_data)),
            ]),

            ConfigurationSection::make('Git Configuration', [
                ConfigurationOption::make(EnvKey::git_enabled)
                    ->question('Should code be deployed from a git repository?')
                    ->confirm()
                    ->default('yes'),
                ConfigurationOption::make(EnvKey::git_repository)
                    ->question('Enter git repository address')
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::git_enabled)),
                ConfigurationOption::make(EnvKey::git_branch)
                    ->question('Enter git branch to deploy')
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get(EnvKey::git_branch)),
            ]),
        ];
    }

    protected function buildServices(): void
    {
        $php = $this->addService(Php::class);

        $nginx = $this->addService(Nginx::class)->phpService($php);
        $nginx->sites()->first()?->root('/var/www/public');

        $this->addService(Scheduler::class);
        $this->addService(Worker::class);
        $this->addService(Composer::class);
        $this->addService(Node::class);

        if (Env::get(EnvKey::redis_enabled)) {
            $this->addService(Redis::class);
        }

        if (Env::get(EnvKey::mailhog_enabled)) {
            $this->addService(MailHog::class)
                ->nginxService($nginx);
        }

        if (Env::get(EnvKey::websocket_enabled)) {
            $this->addService(Websocket::class);
        }

        if (Env::get(EnvKey::db_engine, 'mysql') === 'mysql') {
            $mysql = $this->addService(MySql::class);

            if (Env::get(EnvKey::phpmyadmin_enabled)) {
                $this->addService(PhpMyAdmin::class)
                    ->mysqlService($mysql)
                    ->nginxService($nginx);
            }
        }

        $browserTests = Str::of(Env::get(EnvKey::extra_tools))
            ->explode(',')
            ->each(fn (string $tool) => trim($tool))
            ->contains('browser_tests');

        if ($browserTests) {
            $this->addService(Dusk::class)
                ->nginxService($nginx);
        }
    }

    public function commands(): array
    {
        return Collection::make([
            Artisan::class,
            Deploy::class,
            Init::class,
            Install::class,
            Migrate::class,
            RestartQueue::class,
            Tinker::class,
        ])->when(!Env::production(), fn (Collection $c) => $c->push(Vite::class))
            ->push(...$this->services->flatMap(fn (Service $service) => $service->commands()))
            ->toArray();
    }
}
