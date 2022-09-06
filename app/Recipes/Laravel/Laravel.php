<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection LaravelFunctionsInspection */

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
                ConfigurationOption::make('HOST')
                    ->description('Host exposed to external environments')
                    ->question('Application hostname')
                    ->default('laravel.ktm'),
                ConfigurationOption::make('ENV')
                    ->description('Application environment')
                    ->choices(['local', 'production'])
                    ->default('local'),
                ConfigurationOption::make('PHP_VERSION')
                    ->description('PHP Version')
                    ->default('latest'),
                ConfigurationOption::make('NODE_VERSION')
                    ->description('Node Version')
                    ->default('lts'),
                ConfigurationOption::make('EXPOSE_DOCKER_HOST')
                    ->description('Should Docker Host be exposed to containers (Docker > v20.04 only)?')
                    ->confirm()
                    ->default(false),
                ConfigurationOption::make('BEHIND_PROXY')
                    ->question('Is the application behind a reverse proxy?')
                    ->afterSet(function (string|int|bool $set, Configuration $configuration) {
                        if ($set) {
                            $configuration->set('REVERSE_PROXY_NETWORK', 'reverse_proxy_network');
                        }
                    })
                    ->confirm()
                    ->default(false)
                    ->hidden(),
                ConfigurationOption::make('EXTRA_TOOLS')
                    ->description('Extra tools to be installed')
                    ->question('Install any extra tools?')
                    ->choices(function (Configuration $configuration) {
                        $tools = ['mysql_client', 'libreoffice_writer', 'browser_tests'];

                        if ($configuration->get('ENV') === 'production') {
                            return $tools;
                        }

                        $tools[] = 'xdebug';

                        return $tools;
                    }, true)
                    ->optional(true),
            ]),

            ConfigurationSection::make('Services', [
                ConfigurationOption::make('DB_ENGINE')
                    ->question('Which database engine should be used?')
                    ->choices(['mysql'])
                    ->default('mysql'),

                ConfigurationOption::make('MAILHOG_ENABLED')
                    ->question('Should MailHog be enabled?')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get('ENV') !== 'production'),
                ConfigurationOption::make('WEBSOCKET_ENABLED')
                    ->question('Should Websocket server be enabled?')
                    ->confirm()
                    ->default('no'),
                ConfigurationOption::make('REDIS_ENABLED')
                    ->question('Should Redis be enabled?')
                    ->confirm()
                    ->default('no'),
                ConfigurationOption::make('REDIS_VERSION')
                    ->description('Redis Version')
                    ->choices(['5', '6', '7'])
                    ->default('7')
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get('REDIS_ENABLED')),
            ]),

            ConfigurationSection::make('Network Configuration', [
                ConfigurationOption::make('NGINX_PORT')
                    ->question('Enter nginx exposed port')
                    ->default(80)
                    ->validate(function ($value) {
                        return is_numeric($value);
                    })
                    ->when(fn (Configuration $configuration) => !$configuration->get('BEHIND_PROXY')),

                ConfigurationOption::make('EXTERNAL_CERTIFICATE')
                    ->question("Do you want to set up a custom ssl certificate?
                                        <div class='ml-2'>This setup will allow you to define an external folder to load ssl certificates into nginx setup</div>
                                        <div class='ml-2'>Note: the folder must contain at least the following files:</div>
                                        <div class='ml-2'>- live/[hostname]/fullchain.pem</div>
                                        <div class='ml-2'>- live/[hostname]/privkey.pem</div>
                                        <div class='ml-2'>Do you want to proceed?")
                    ->confirm()
                    ->default(false)
                    ->hidden()
                    ->when(fn (Configuration $configuration) => (!$configuration->get('BEHIND_PROXY') && $configuration->get('NGINX_PORT') == 443) || $configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('NGINX_EXTERNAL_CERTIFICATE_FOLDER')
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
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get('EXTERNAL_CERTIFICATE')),
                ConfigurationOption::make('NGINX_EXTERNAL_CERTIFICATE_HOSTNAME')
                    ->question('Enter the hostname contained in the certificate')
                    ->default(fn (Configuration $configuration) => $configuration->get('HOST'))
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get('EXTERNAL_CERTIFICATE')),

            ]),

            ConfigurationSection::make('Database Configuration', [
                ConfigurationOption::make('MYSQL_PORT')
                    ->question('Enter mysql exposed port')
                    ->default(3306)
                    ->validate(fn ($value) => is_numeric($value))
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql')
                    ->optional(),

                ConfigurationOption::make('MYSQL_DATABASE')
                    ->description('Database name')
                    ->default('database')
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql'),
                ConfigurationOption::make('MYSQL_USER')
                    ->description('Database user')
                    ->default('dbuser')
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql'),
                ConfigurationOption::make('MYSQL_PASSWORD')
                    ->description('Database password')
                    ->default('dbpassword')
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql'),
                ConfigurationOption::make('MYSQL_ROOT_PASSWORD')
                    ->description('Database root password')
                    ->default('root')
                    ->validate(fn (string|int|bool $value, Configuration $configuration) => $value === 'root' && $configuration->get('ENV') === 'production'
                        ? "you should not use 'root' in production environments"
                        : true
                    )
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql'),
                ConfigurationOption::make('MYSQL_DISABLE_STRICT_MODE')
                    ->description('Disable strict mode')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql'),

                ConfigurationOption::make('PHPMYADMIN_ENABLED')
                    ->question('Should PHPMyAdmin be enabled?')
                    ->confirm()
                    ->default('no')
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql' && $configuration->get('ENV') !== 'production'),
                ConfigurationOption::make('PHPMYADMIN_PORT')
                    ->question('Enter PHPMyAdmin exposed port')
                    ->default(8081)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql' && $configuration->get('PHPMYADMIN_ENABLED') && !$configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('PHPMYADMIN_SUBDOMAIN')
                    ->question('Enter PHPMyAdmin exposed subdomain')
                    ->default('db')
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get('DB_ENGINE') === 'mysql' && $configuration->get('PHPMYADMIN_ENABLED')),
            ]),

            ConfigurationSection::make('MailHog Configuration', [
                ConfigurationOption::make('MAILHOG_PORT')
                    ->question('Enter MailHog exposed port')
                    ->default(8025)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => $configuration->get('MAILHOG_ENABLED') && !$configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('MAILHOG_SUBDOMAIN')
                    ->question('Enter MailHog exposed subdomain')
                    ->default('mail')
                    ->optional()
                    ->when(fn (Configuration $configuration) => (bool) $configuration->get('MAILHOG_ENABLED')),
            ]),

            ConfigurationSection::make('Websocket Configuration', [
                ConfigurationOption::make('WEBSOCKET_PORT')
                    ->question('Enter Websocket server exposed port')
                    ->default(6001)
                    ->validate(fn ($value) => is_numeric($value))
                    ->when(fn (Configuration $configuration) => $configuration->get('WEBSOCKET_ENABLED') && !$configuration->get('BEHIND_PROXY')),
            ]),
        ];
    }

    protected function buildServices(): void
    {
        $this->addService(Php::class);

        $nginx = $this->addService(Nginx::class)
            ->phpService('php');

        $this->addService(Scheduler::class);
        $this->addService(Worker::class);
        $this->addService(Composer::class);
        $this->addService(Node::class);

        if ((bool) env('REDIS_ENABLED')) {
            $this->addService(Redis::class);
        }

        if ((bool) env('MAILHOG_ENABLED')) {
            $this->addService(MailHog::class)
                ->nginxService($nginx);
        }

        if ((bool) env('WEBSOCKET_ENABLED')) {
            $this->addService(Websocket::class);
        }

        if (env('DB_ENGINE') === 'mysql') {
            $mysql = $this->addService(MySql::class);

            if ((bool) env('PHPMYADMIN_ENABLED')) {
                $this->addService(PhpMyAdmin::class)
                    ->mysqlService($mysql)
                    ->nginxService($nginx);
            }
        }

        $browserTests = Str::of(env('EXTRA_TOOLS'))
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
            Vite::class,
        ])->when(env('ENV') !== 'production', fn (Collection $c) => $c->push(Vite::class))
            ->push(...$this->services->flatMap(fn (Service $service) => $service->commands()))
            ->toArray();
    }
}
