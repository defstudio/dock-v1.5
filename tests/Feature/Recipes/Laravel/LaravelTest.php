<?php

use App\Facades\Terminal;
use App\Recipes\Laravel\Laravel;

test('setup', function (array $steps) {
    Terminal::fake($steps);

    $recipe = new Laravel();
    $recipe->setup();

    Terminal::assertAllExpectedMessageSent();
})->with([
    'default path' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'local',
            'PHP Version' => '8.1',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'no',
            'Network Configuration',
            'Do you want to set up a custom ssl certificate? This setup will allow you to define an external folder to load ssl certificates into nginx setup Note: the folder must contain at least the following files: - live/[hostname]/fullchain.pem - live/[hostname]/privkey.pem Do you want to proceed?' => 'no',
            'Enter nginx exposed port' => '',
            'Enter mysql exposed port' => '',
            'Enter PHPMyAdmin exposed port' => 'x',
            'Enter PHPMyAdmin exposed subdomain' => 'x',
            'Enter MailHog exposed port' => 'x',
            'Enter MailHog exposed subdomain' => 'x',
            'Enter Websocket server exposed port' => 'x',
            'Database Configuration',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
        ],
    ],
    'production requires mysql strong root password' => [
        'steps' => [
            'General',
            'Application hostname' => 'test.ktm',
            'Application environment' => 'production',
            'PHP Version' => '8.1',
            'Should Docker Host be exposed to containers (Docker > v20.04 only)?' => 'no',
            'Is the application behind a reverse proxy?' => 'no',
            'Network Configuration',
            'Do you want to set up a custom ssl certificate? This setup will allow you to define an external folder to load ssl certificates into nginx setup Note: the folder must contain at least the following files: - live/[hostname]/fullchain.pem - live/[hostname]/privkey.pem Do you want to proceed?' => 'no',
            'Enter nginx exposed port' => '',
            'Enter mysql exposed port' => '',
            'Enter PHPMyAdmin exposed port' => 'x',
            'Enter PHPMyAdmin exposed subdomain' => 'x',
            'Enter MailHog exposed port' => 'x',
            'Enter MailHog exposed subdomain' => 'x',
            'Enter Websocket server exposed port' => 'x',
            'Database Configuration',
            'Database name' => '',
            'Database user' => '',
            'Database password' => '',
            'Database root password' => '',
            "Error: you should not use 'root' in production environments",
            '<2>Database root password' => 'foo',
        ],
    ],
]);
