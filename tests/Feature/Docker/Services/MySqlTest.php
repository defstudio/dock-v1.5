<?php

declare(strict_types=1);

use App\Docker\Services\MySql;
use App\Enums\EnvKey;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'test.ktm']);
});

it('sets its service name', function () {
    expect(new MySql())->name()->toBe('mysql');
});

it('sets its yml', function () {
    expect(new MySql())->yml()->toMatchSnapshot();
});

it('can set database data from env', function (EnvKey $key, string $value, string $imageEnvKey) {
    Env::put($key, $value);

    expect(new MySql())->yml("environment.$imageEnvKey")->toBe($value);
})->with([
    [EnvKey::db_name, 'foo', 'MYSQL_DATABASE'],
    [EnvKey::db_user, 'bar', 'MYSQL_USER'],
    [EnvKey::db_password, 'baz', 'MYSQL_PASSWORD'],
    [EnvKey::db_root_password, 'zap', 'MYSQL_ROOT_PASSWORD'],
]);

it('can set database name', function () {
    $mysql = new MySql();
    $mysql->setDatabaseName('foo');

    expect($mysql)->yml('environment.MYSQL_DATABASE')->toBe('foo');
});

it('can set database user', function () {
    $mysql = new MySql();
    $mysql->setDatabaseUser('baz');

    expect($mysql)->yml('environment.MYSQL_USER')->toBe('baz');
});

it('can set database password', function () {
    $mysql = new MySql();
    $mysql->setDatabasePassword('bar');

    expect($mysql)->yml('environment.MYSQL_PASSWORD')->toBe('bar');
});

it('can set database root password', function () {
    $mysql = new MySql();
    $mysql->setDatabaseRootPassword('quz');

    expect($mysql)->yml('environment.MYSQL_ROOT_PASSWORD')->toBe('quz');
});

it('can disable strict mode', function () {
    Env::put(EnvKey::db_disable_strict_mode, '1');
    expect(new MySql())->yml('command')->toEndWith('--sql_mode=""');
});

it('can set its port', function () {
    Env::put(EnvKey::db_port, 42);

    expect(new MySql())
        ->yml('ports')->toBe(['42:3306'])
        ->yml('expose')->toBe([3306]);
});

it('sets its volumes', function () {
    expect(new MySql())->toHaveVolume('./volumes/mysql/db', '/var/lib/mysql');
});

it('adds internal network', function () {
    expect(new MySql())->toHaveNetwork('test_ktm_internal_network');
});

test('commands', function () {
    expect(new MySql())->commands()->toBe([]);
});
