<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Php;
use App\Exceptions\DockerServiceException;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'foo']);
});

it('sets its service name', function () {
    expect(new Php())->name()->toBe('php');
});

it('sets its yml', function () {
    expect(new Php())->yml()->toMatchSnapshot();
});

it('can expose docker host', function () {
    Env::put(\App\Enums\EnvKey::expose_docker_host, 1);

    expect(new Php())->yml('extra_hosts')->toBe([
        'host.docker.internal:host-gateway',
    ]);
});

it('can set its dependency from redis', function () {
    Env::put(\App\Enums\EnvKey::redis_enabled, 1);

    expect(new Php())->yml('depends_on')->toContain('redis');
});

it('can set its dependency from mysql', function () {
    Env::put(\App\Enums\EnvKey::db_engine, 'mysql');

    expect(new Php())->yml('depends_on')->toContain('mysql');
});

it('sets its volumes', function () {
    expect(new Php())
        ->toHaveVolume('./src', '/var/www')
        ->toHaveVolume('./services/php/php.ini', '/usr/local/etc/php/php.ini');
});

it('adds internal network', function () {
    expect(new Php())->toHaveNetwork('foo_internal_network');
});

test('default php version', function () {
    expect(new Php())->getPhpVersion()->toBe('latest');
});

test('can customize php version', function () {
    Env::put(\App\Enums\EnvKey::php_version, '7.4.91');
    expect(new Php())->getPhpVersion()->toBe('7.4.91');
});

it('can customize its build target', function (string $target) {
    $php = new Php();
    $php->target($target);

    expect($php)->yml('build.target')->toBe($target);
})->with(['fpm', 'composer', 'scheduler', 'websocket', 'worker']);

it('prevents to set up an invalid target', function () {
    $php = new Php();
    $php->target('foo');
})->throws(DockerServiceException::class, 'Invalid PHP target: [foo]');

it('enables tools from env', function () {
    Env::put(\App\Enums\EnvKey::extra_tools, 'xdebug,libreoffice_writer,mysql_client,pcov');

    expect(new Php())
        ->isXdebugEnabled()->toBeTrue()
        ->isLibreOfficeWriterEnabled()->toBeTrue()
        ->isMySqlClientEnabled()->toBeTrue()
        ->isPcovEnabled()->toBeTrue();
});

it('force xdebug to be disabled in production', function () {
    Env::put(\App\Enums\EnvKey::env, 'production')->put(\App\Enums\EnvKey::extra_tools, 'xdebug,libreoffice_writer,mysql_client');

    expect(new Php())
        ->isXdebugEnabled()->toBeFalse()
        ->isPcovEnabled()->toBeFalse()
        ->isLibreOfficeWriterEnabled()->toBeTrue()
        ->isMySqlClientEnabled()->toBeTrue();
});

it('returns system packages to be installed', function (array $env) {
    Env::fake($env);
    expect(new Php())->systemPackages()->toMatchSnapshot();
})->with([
    'default' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'foo'],
    'with mysql client' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'foo', 'EXTRA_TOOLS' => 'mysql_client'],
]);

it('returns php extensions to be installed', function () {
    expect(new Php())->phpExtensions()->toMatchSnapshot();
});

it('checks if redis is enabled', function () {
    expect(new Php())->isRedisEnabled()->toBeFalse();
    Env::put(\App\Enums\EnvKey::redis_enabled, 1);
    expect(new Php())->isRedisEnabled()->toBeTrue();
});

it('computes PHP major version', function (string|float $version, int $expected) {
    Env::put(\App\Enums\EnvKey::php_version, $version);

    expect(new Php())->phpMajorVersion()->toBe($expected);
})->with([
    ['version' => 'latest', 'expected' => 8],
    ['version' => 7, 'expected' => 7],
    ['version' => '5', 'expected' => 5],
    ['version' => 7.4, 'expected' => 7],
    ['version' => '8.2', 'expected' => 8],
    ['version' => '8.1.10', 'expected' => 8],
]);

it('computes PHP minor version', function (string|float $version, float $expected) {
    Env::put(\App\Enums\EnvKey::php_version, $version);

    expect(new Php())->getPhpMinorVersion()->toBe($expected);
})->with([
    ['version' => 'latest', 'expected' => 8.1],
    ['version' => 7, 'expected' => 7.0],
    ['version' => 7.4, 'expected' => 7.4],
    ['version' => '5', 'expected' => 5.0],
    ['version' => '7.2', 'expected' => 7.2],
    ['version' => '8.1.10', 'expected' => 8.1],
    ['version' => '8.1.10', 'expected' => 8.1],
    ['version' => '8.2.0RC1', 'expected' => 8.2],
]);

it('forces asset folder to services/php', function () {
    $php = new Php();
    $php->setServiceName('foo');

    expect(invade($php)->assetsFolder())->toBe('./services/php');
});

test('commands', function () {
    expect(new Php())->commands()->toBe([\App\Docker\Services\Commands\Php::class]);
});

it('publishes assets', function (string $asset, array $env, string $phpVersion) {
    Env::fake($env)->put(\App\Enums\EnvKey::php_version, $phpVersion);

    Service::fake();

    $php = new Php();
    $php->publishAssets();

    expect($php->assets()->get($asset) ?? '')->toMatchTextSnapshot();
})->with([
    'build/Dockerfile',
    'php.ini',
])->with([
    'default' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.com'],
    'production' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.com', 'ENV' => 'production'],
    'with extra tools' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.com', 'EXTRA_TOOLS' => 'mysql_client,libreoffice_writer,xdebug,pcov'],
    'with libreoffice writer' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.com', 'EXTRA_TOOLS' => 'xdebug'],
    'with redis' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.com', 'REDIS_ENABLED' => true],
])->with('php versions');
