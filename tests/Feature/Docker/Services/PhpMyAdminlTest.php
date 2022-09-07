<?php

declare(strict_types=1);

use App\Docker\Services\MySql;
use App\Docker\Services\Nginx;
use App\Docker\Services\PhpMyAdmin;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'bar.ktm']);
});

it('sets its service name', function () {
    expect(new PhpMyAdmin())->name()->toBe('phpmyadmin');
});

it('sets its yml', function () {
    expect(new PhpMyAdmin())->yml()->toMatchSnapshot();
});

it('can set a custom port', function () {
    Env::put('PHPMYADMIN_PORT', 44);

    expect(new PhpMyAdmin())->yml('ports')->toBe(['44:80']);
});

it('adds internal network', function () {
    expect(new PhpMyAdmin())->toHaveNetwork('test-recipe_internal_network');
});

it('sets credentials from mysql configuration', function () {
    $mysql = new MySql();
    $mysql->setDatabaseRootPassword('foo');
    $mysql->setServiceName('bar');

    $phpmyadmin = new PhpMyAdmin();
    $phpmyadmin->mysqlService($mysql);

    expect($phpmyadmin)
        ->yml('environment.MYSQL_ROOT_PASSWORD')->toBe('foo')
        ->yml('environment.PMA_HOST')->toBe('bar');
});

it('adds its subdomain to Nginx service', function () {
    Env::put('PHPMYADMIN_SUBDOMAIN', 'foo');

    $nginx = new Nginx();
    $phpmyadmin = new PhpMyAdmin();

    $phpmyadmin->nginxService($nginx);

    $site = $nginx->getSite('foo.bar.ktm');

    expect($site->configuration())->toMatchTextSnapshot();
});

test('commands', function () {
    expect(new PhpMyAdmin())->commands()->toBe([]);
});
