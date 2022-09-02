<?php

namespace App\Recipes\Laravel;

use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;
use App\Recipes\ConfigurationSection;
use League\Flysystem\Config;

class Laravel extends \App\Recipes\Recipe
{
    public function name(): string
    {
        return "Laravel";
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
                    ->default('no'),
                ConfigurationOption::make('BEHIND_PROXY')
                    ->question('Is the application behind a reverse proxy?')
                    ->afterSet(function (Configuration $configuration) {
                        if ($configuration->get('BEHIND_PROXY')) {
                            $configuration->set('REVERSE_PROXY_NETWORK', 'reverse_proxy_network');
                        }
                    })
                    ->confirm()
                    ->hidden(),
            ]),
            ConfigurationSection::make('Network Configuration', [
                ConfigurationOption::make('NGINX_PORT')
                    ->question('Enter nginx exposed port')
                    ->default(80)
                    ->validate(fn ($value) => is_int($value))
                    ->when(fn (Configuration $configuration) => !$configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('MYSQL_PORT')
                    ->question('Enter mysql exposed port')
                    ->default(3306)
                    ->validate(fn ($value) => is_int($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => !$configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('PHPMYADMIN_PORT')
                    ->question('Enter PHPMyAdmin exposed port')
                    ->default(8081)
                    ->validate(fn ($value) => is_int($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => !$configuration->get('BEHIND_PROXY')),
                ConfigurationOption::make('MAILHOG_PORT')
                    ->question('Enter MailHog exposed port')
                    ->default(8025)
                    ->validate(fn (string $value) => is_int($value))
                    ->optional()
                    ->when(fn (Configuration $configuration) => !$configuration->get('BEHIND_PROXY')),
            ]),
        ];
    }
}
