<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Php;
use App\Exceptions\DockerServiceException;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Php())->name()->toBe('php');
});

it('sets its yml', function () {
    expect(new Php())->yml()->toMatchSnapshot();
});

it('can expose docker host', function () {
    Env::put('EXPOSE_DOCKER_HOST', 1);

    expect(new Php())->yml('extra_hosts')->toBe([
        'host.docker.internal:host-gateway',
    ]);
});

it('can set its dependency from redis', function () {
    Env::put('REDIS_ENABLED', 1);

    expect(new Php())->yml('depends_on')->toContain('redis');
});

it('can set its dependency from mysql', function () {
    Env::put('DB_ENGINE', 'mysql');

    expect(new Php())->yml('depends_on')->toContain('mysql');
});

it('sets its volumes', function () {
    expect(new Php())->toHaveVolume('./src', '/var/www');
});

it('adds internal network', function () {
    expect(new Php())->toHaveNetwork('test-recipe_internal_network');
});

test('default php version', function () {
    expect(new Php())->getPhpVersion()->toBe('latest');
});

test('can customize php version', function () {
    Env::put('PHP_VERSION', '7.4.91');
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
    Env::put('EXTRA_TOOLS', 'xdebug,libreoffice_writer,mysql_client,pcov');

    expect(new Php())
        ->isXdebugEnabled()->toBeTrue()
        ->isLibreOfficeWriterEnabled()->toBeTrue()
        ->isMySqlClientEnabled()->toBeTrue()
        ->isPcovEnabled()->toBeTrue();
});

it('force xdebug to be disabled in production', function () {
    Env::put('ENV', 'production')->put('EXTRA_TOOLS', 'xdebug,libreoffice_writer,mysql_client');

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
    'default' => fn () => ['RECIPE' => 'test-recipe'],
    'with mysql client' => fn () => ['RECIPE' => 'test-recipe', 'EXTRA_TOOLS' => 'mysql_client'],
]);

it('returns php extensions to be installed', function () {
    expect(new Php())->phpExtensions()->toMatchSnapshot();
});

it('checks if redis is enabled', function () {
    expect(new Php())->isRedisEnabled()->toBeFalse();
    Env::put('REDIS_ENABLED', 1);
    expect(new Php())->isRedisEnabled()->toBeTrue();
});

it('computes PHP major version', function (string|float $version, int $expected) {
    Env::put('PHP_VERSION', $version);

    expect(new Php())->phpMajorVersion()->toBe($expected);
})->with([
    ['version' => 'latest', 'major' => 8],
    ['version' => 7, 'major' => 7],
    ['version' => '5', 'major' => 5],
    ['version' => 7.4, 'major' => 7],
    ['version' => '8.2', 'major' => 8],
    ['version' => '8.1.10', 'major' => 8],
]);

it('foces asset folder to services/php', function () {
    $php = new Php();
    $php->setServiceName('foo');

    expect(invade($php)->assetsFolder())->toBe('./services/php');
});

test('commands', function () {
    expect(new Php())->commands()->toBe([]);
});

it('publishes Dockerfile', function ($env) {
    Env::fake($env);
    Service::fake();

    $php = new Php();
    $php->publishAssets();

    expect($php->assets()->get('Dockerfile'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
    'custom php version' => fn() => ['RECIPE' => 'test-recipe', 'PHP_VERSION' => "8.1.5"],
    'with mysql client' => fn() => ['RECIPE' => 'test-recipe', 'EXTRA_TOOLS' => "mysql_client"],
    'with libreoffice writer' => fn() => ['RECIPE' => 'test-recipe', 'EXTRA_TOOLS' => "libreoffice_writer"],
    'with redis' => fn() => ['RECIPE' => 'test-recipe', 'REDIS_ENABLED' => true],
]);

it('publishes php.ini', function ($env) {
    Env::fake($env);
    Service::fake();

    $php = new Php();
    $php->publishAssets();

    expect($php->assets()->get('php.ini'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
    'production' => fn() => ['RECIPE' => 'test-recipe', 'ENV' => 'production'],
]);
