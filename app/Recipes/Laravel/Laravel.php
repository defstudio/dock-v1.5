<?php

declare(strict_types=1);

namespace App\Recipes\Laravel;

use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;
use App\Recipes\ConfigurationSection;
use App\Recipes\Recipe;
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
            ConfigurationSection::make('Network Configuration', [
                ConfigurationOption::make('EXTERNAL_CERTIFICATE')
                    ->question("Do you want to set up a custom ssl certificate?
                                        <div class='ml-2'>This setup will allow you to define an external folder to load ssl certificates into nginx setup</div>
                                        <div class='ml-2'>Note: the folder must contain at least the following files:</div>
                                        <div class='ml-2'>- live/[hostname]/fullchain.pem</div>
                                        <div class='ml-2'>- live/[hostname]/privkey.pem</div>
                                        <div class='ml-2'>Do you want to proceed?")
                    ->confirm()
                    ->hidden()
                    ->default(false),
                ConfigurationOption::make('NGINX_CUSTOM_CERTIFICATES_FOLDER')
                    ->question('Enter the path to the ssl certificates folder (absolute or relative to dock folder)')
                    ->validate(function (string|int|bool $path): string|bool {
                        $path = "$path";

                        $exists = Str::of($path)->startsWith(DIRECTORY_SEPARATOR)
                            ? File::exists($path)
                            : Storage::disk('cwd')->exists($path);

                        if (! $exists) {
                            return 'Invalid path';
                        }

                        return true;
                    })
                    ->when(fn (Configuration $configuration) => $configuration->get('EXTERNAL_CERTIFICATE')),
                ConfigurationOption::make('NGINX_CUSTOM_CERTIFICATES_HOSTNAME')
                    ->question('Enter the hostname contained in the certificate')
                    ->default(fn (Configuration $configuration) => $configuration->get('HOST'))
                    ->when(fn (Configuration $configuration) => $configuration->get('EXTERNAL_CERTIFICATE')),
                ConfigurationOption::make('NGINX_PORT')
                    ->question('Enter nginx exposed port')
                    ->default(80)
                    ->validate(function ($value) {
                        return is_numeric($value);
                    })
                    ->when(fn (Configuration $configuration) => ! $configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('MYSQL_PORT')
                    ->question('Enter mysql exposed port')
                    ->default(3306)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional(),
                ConfigurationOption::make('PHPMYADMIN_PORT')
                    ->question('Enter PHPMyAdmin exposed port')
                    ->default(8081)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => ! $configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('PHPMYADMIN_SUBDOMAIN')
                    ->question('Enter PHPMyAdmin exposed subdomain')
                    ->default('db')
                    ->optional(),
                ConfigurationOption::make('MAILHOG_PORT')
                    ->question('Enter MailHog exposed port')
                    ->default(8025)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => ! $configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('MAILHOG_SUBDOMAIN')
                    ->question('Enter MailHog exposed subdomain')
                    ->default('mail')
                    ->optional(),
                ConfigurationOption::make('WEBSOCKET_PORT')
                    ->question('Enter Websocket server exposed port')
                    ->default(6001)
                    ->validate(fn ($value) => is_numeric($value))
                    ->optional(),
            ]),
            ConfigurationSection::make('Database Configuration', [
                ConfigurationOption::make('MYSQL_DATABASE')
                    ->description('Database name')
                    ->default('database'),
                ConfigurationOption::make('MYSQL_USER')
                    ->description('Database user')
                    ->default('dbuser'),
                ConfigurationOption::make('MYSQL_PASSWORD')
                    ->description('Database password')
                    ->default('dbpassword'),
                ConfigurationOption::make('MYSQL_ROOT_PASSWORD')
                    ->description('Database root password')
                    ->default('root')
                    ->validate(fn (string|int|bool $value, Configuration $configuration) => $value === 'root' && $configuration->get('ENV') === 'production'
                        ? "you should not use 'root' in production environments"
                        : true
                    ),
            ]),
        ];
    }
}
