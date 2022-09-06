<?php

declare(strict_types=1);

use App\Facades\Terminal;
use App\Recipes\Laravel\Laravel;
use Illuminate\Support\Facades\Storage;

test('setup', function (array $steps, array $config) {
    Terminal::fake($steps);
    Storage::fake('cwd');

    $recipe = new Laravel();
    $configuration = $recipe->setup();

    Terminal::assertAllExpectedMessageSent();

    foreach ($config as $key => $value) {
        expect($configuration->get($key))->toBe($value);
    }
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
        'config' => [
            'HOST' => 'test.ktm',
            'ENV' => 'local',
            'PHP_VERSION' => '8.1',
            'NODE_VERSION' => 'lts',
            'EXPOSE_DOCKER_HOST' => false,
            'BEHIND_PROXY' => false,
            'EXTERNAL_CERTIFICATE' => false,
            'DATABASE_ENGINE' => 'mysql',
            'NGINX_PORT' => '80',
            'MYSQL_PORT' => '3306',
            'PHPMYADMIN_PORT' => '',
            'PHPMYADMIN_SUBDOMAIN' => '',
            'MAILHOG_PORT' => '',
            'MAILHOG_SUBDOMAIN' => '',
            'WEBSOCKET_PORT' => '',

            'MYSQL_DATABASE' => 'database',
            'MYSQL_USER' => 'dbuser',
            'MYSQL_PASSWORD' => 'dbpassword',
            'MYSQL_ROOT_PASSWORD' => 'root',
        ],
    ],
])->only();
