<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Commands\Composer as ComposerCommand;
use App\Docker\Services\Commands\NginxRestart as NginxRestartCommand;
use App\Docker\Services\Commands\Node as NodeCommand;
use App\Docker\Services\Commands\Npm as NpmCommand;
use App\Facades\Env;
use App\Facades\Terminal;
use App\Recipes\Laravel\Commands\Artisan as ArtisanCommand;
use App\Recipes\Laravel\Commands\Deploy as DeployCommand;
use App\Recipes\Laravel\Commands\Init as InitCommand;
use App\Recipes\Laravel\Commands\Install as InstallCommand;
use App\Recipes\Laravel\Commands\Migrate as MigrateCommand;
use App\Recipes\Laravel\Commands\RestartQueue as RestartQueueCommand;
use App\Recipes\Laravel\Commands\Tinker as TinkerCommand;
use App\Recipes\Laravel\Commands\Vite as ViteCommand;
use App\Recipes\Laravel\Laravel;
use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;

test('user can set the configuration', function (array $steps) {
    Terminal::fake($steps);
    Storage::fake('cwd');

    restoreDefaultRecipes();

    $recipe = new Laravel();
    $configuration = $recipe->setup();

    Terminal::assertAllExpectedMessageSent();

    expect($configuration->toArray())->toMatchSnapshot();
})->with([
    'default path' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'local',
            'PHP Version' => '8.1',
            'Node Version' => 'lts',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'no',
            'Install any extra tools?' => 'xdebug',
            '<2>Install any extra tools?' => 'mysql_client',
            '<3>Install any extra tools?' => '',
            'Services',
            'Which database engine should be used?' => 'mysql',
            'Should MailHog be enabled?' => 'yes',
            'Should Websocket server be enabled?' => 'yes',
            'Should Redis be enabled?' => 'yes',
            'Redis Version' => 7,
            'Network Configuration',
            'Enter nginx exposed port' => '',
            'Database Configuration',
            'Enter mysql exposed port' => '',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
            'Disable strict mode' => 'no',
            'Should PHPMyAdmin be enabled?' => 'yes',
            'Enter PHPMyAdmin exposed port' => 'x',
            'Enter PHPMyAdmin exposed subdomain' => 'x',
            'MailHog Configuration',
            'Enter MailHog exposed port' => 'x',
            'Enter MailHog exposed subdomain' => 'x',
            'Websocket Configuration',
            'Enter Websocket server exposed port' => '',
            'SUCCESS!',
            'The configuration has been stored in .env file',
        ],
    ],
    'for production' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'production',
            'PHP Version' => '8.1',
            'Node Version' => 'lts',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'no',
            'Install any extra tools?' => 'xdebug',
            '<2>Install any extra tools?' => 'mysql_client',
            '<3>Install any extra tools?' => '',
            'Services',
            'Which database engine should be used?' => 'mysql',
            'Should Websocket server be enabled?' => 'yes',
            'Should Redis be enabled?' => 'yes',
            'Redis Version' => 7,
            'Network Configuration',
            'Enter nginx exposed port' => '',
            'Database Configuration',
            'Enter mysql exposed port' => '',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
            "Error: you should not use 'root' in production environments",
            '<2>Database root password' => 'foo',
            'Disable strict mode' => 'no',
            'Websocket Configuration',
            'Enter Websocket server exposed port' => '',
            'SUCCESS!',
            'The configuration has been stored in .env file',
        ],
    ],
    'behind reverse proxy' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'local',
            'PHP Version' => '8.1',
            'Node Version' => 'lts',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'yes',
            'Install any extra tools?' => 'xdebug',
            '<2>Install any extra tools?' => 'mysql_client',
            '<3>Install any extra tools?' => '',
            'Services',
            'Which database engine should be used?' => 'mysql',
            'Should MailHog be enabled?' => 'yes',
            'Should Websocket server be enabled?' => 'yes',
            'Should Redis be enabled?' => 'yes',
            'Redis Version' => 7,
            'Network Configuration',
            'Do you want to set up a custom ssl certificate? This setup will allow you to define an external folder to load ssl certificates into nginx setup Note: the folder must contain at least the following files: - live/[hostname]/fullchain.pem - live/[hostname]/privkey.pem Do you want to proceed?' => 'no',
            'Database Configuration',
            'Enter mysql exposed port' => '',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
            'Disable strict mode' => 'no',
            'Should PHPMyAdmin be enabled?' => 'yes',
            'Enter PHPMyAdmin exposed subdomain' => 'x',
            'MailHog Configuration',
            'Enter MailHog exposed subdomain' => 'x',
            'SUCCESS!',
            'The configuration has been stored in .env file',
        ],
    ],
    'external certificate' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'local',
            'PHP Version' => '8.1',
            'Node Version' => 'lts',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'no',
            'Install any extra tools?' => 'xdebug',
            '<2>Install any extra tools?' => 'mysql_client',
            '<3>Install any extra tools?' => '',
            'Services',
            'Which database engine should be used?' => 'mysql',
            'Should MailHog be enabled?' => 'yes',
            'Should Websocket server be enabled?' => 'yes',
            'Should Redis be enabled?' => 'yes',
            'Redis Version' => 7,
            'Network Configuration',
            'Enter nginx exposed port' => '443',
            'Do you want to set up a custom ssl certificate? This setup will allow you to define an external folder to load ssl certificates into nginx setup Note: the folder must contain at least the following files: - live/[hostname]/fullchain.pem - live/[hostname]/privkey.pem Do you want to proceed?' => 'yes',
            'Enter the path to the ssl certificates folder (absolute or relative to dock folder)' => 'foo',
            'Error: Invalid path',
            '<2>Enter the path to the ssl certificates folder (absolute or relative to dock folder)' => '/tmp',
            'Enter the hostname contained in the certificate' => '*.test.ktm',
            'Database Configuration',
            'Enter mysql exposed port' => '',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
            'Disable strict mode' => 'no',
            'Should PHPMyAdmin be enabled?' => 'yes',
            'Enter PHPMyAdmin exposed port' => 'x',
            'Enter PHPMyAdmin exposed subdomain' => 'x',
            'MailHog Configuration',
            'Enter MailHog exposed port' => 'x',
            'Enter MailHog exposed subdomain' => 'x',
            'Websocket Configuration',
            'Enter Websocket server exposed port' => '',
            'SUCCESS!',
            'The configuration has been stored in .env file',
        ],
    ],
]);

test('commands', function () {
    Env::fake([
        'RECIPE' => 'laravel',
        'HOST' => 'foo.com',
    ]);

    app()->bind(RecipeService::class, fn () => new RecipeService());

    $laravel = new App\Recipes\Laravel\Laravel();
    $laravel->build();

    expect($laravel)
        ->commands()
        ->toBe([
            ArtisanCommand::class,
            DeployCommand::class,
            InitCommand::class,
            InstallCommand::class,
            MigrateCommand::class,
            RestartQueueCommand::class,
            TinkerCommand::class,
            ViteCommand::class,
            NginxRestartCommand::class,
            ComposerCommand::class,
            NodeCommand::class,
            NpmCommand::class,
        ]);
});

test('commands in production', function () {
    Env::fake([
        'RECIPE' => 'laravel',
        'ENV' => 'production',
        'HOST' => 'foo.com',
    ]);

    app()->bind(RecipeService::class, fn () => new RecipeService());

    $laravel = new App\Recipes\Laravel\Laravel();
    $laravel->build();

    expect($laravel)
        ->commands()
        ->toBe([
            ArtisanCommand::class,
            DeployCommand::class,
            InitCommand::class,
            InstallCommand::class,
            MigrateCommand::class,
            RestartQueueCommand::class,
            TinkerCommand::class,
            NginxRestartCommand::class,
            ComposerCommand::class,
            NodeCommand::class,
            NpmCommand::class,
        ]);
});

it('builds the right services', function (array $env) {
    Env::fake($env);
    app()->bind(RecipeService::class, fn () => new RecipeService());

    $laravel = new App\Recipes\Laravel\Laravel();
    $laravel->build();

    expect($laravel)->services()->map(fn (Service $service) => $service::class)->toMatchSnapshot();
})->with([
    'default' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
        ],
    ],
    'redis enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'REDIS_ENABLED' => 1,
        ],
    ],
    'mailhog enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'MAILHOG_ENABLED' => 1,
        ],
    ],
    'websocket enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'WEBSOCKET_ENABLED' => 1,
        ],
    ],
    'phpmyadmin enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'PHPMYADMIN_ENABLED' => 1,
        ],
    ],
    'dusk enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'EXTRA_TOOLS' => 'browser_tests',
        ],
    ],
]);

it('publishes docker-compose file', function (array $env) {
    Service::fake();
    Env::fake($env);
    Storage::fake('cwd');

    restoreDefaultRecipes();

    $laravel = new App\Recipes\Laravel\Laravel();
    $laravel->build();
    $laravel->publishDockerCompose();

    expect(Storage::disk('cwd'))->get('docker-compose.yml')->toMatchSnapshot();
})->with([
    'default' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
        ],
    ],
    'redis enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'REDIS_ENABLED' => 1,
        ],
    ],
    'mailhog enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'MAILHOG_ENABLED' => 1,
        ],
    ],
    'websocket enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'WEBSOCKET_ENABLED' => 1,
        ],
    ],
    'phpmyadmin enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'PHPMYADMIN_ENABLED' => 1,
        ],
    ],
    'dusk enabled' => [
        [
            'RECIPE' => 'laravel',
            'HOST' => 'foo.com',
            'EXTRA_TOOLS' => 'browser_tests',
        ],
    ],
]);
