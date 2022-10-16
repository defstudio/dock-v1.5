<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Commands\NginxRestart;
use App\Docker\Services\Nginx;
use App\Docker\Services\Php;
use App\Docker\Site;
use App\Enums\EnvKey;
use App\Exceptions\DockerServiceException;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'foo.test']);
});

it('sets its service name', function () {
    expect(new Nginx())->name()->toBe('nginx');
});

it('sets its yml', function () {
    expect(new Nginx())->yml()->toMatchSnapshot();
});

it('can expose docker host', function () {
    Env::put(EnvKey::expose_docker_host, 1);

    expect(new Nginx())->yml('extra_hosts')->toBe([
        'host.docker.internal:host-gateway',
    ]);
});

it('sets its volumes', function () {
    expect(new Nginx())
        ->toHaveVolume('./src', '/var/www')
        ->toHaveVolume('./services/nginx/nginx.conf', '/etc/nginx/nginx.conf')
        ->toHaveVolume('./services/nginx/sites-available', '/etc/nginx/sites-available');
});

it('adds internal network', function () {
    expect(new Nginx())->toHaveNetwork('foo_test_internal_network');
});

it('can add reverse proxy network', function () {
    Env::put(EnvKey::reverse_proxy_network, 'foo-network');

    expect(new Nginx())
        ->toHaveNetwork('foo-network')
        ->getNetworks()->get('foo-network')->toArray()->toBe(['external' => true]);
});

it('can set php service dependency', function () {
    $php = new Php();
    $nginx = new Nginx();

    $nginx->phpService($php);

    expect($nginx)
        ->toHaveVolume('./services/nginx/conf.d/upstream.conf', '/etc/nginx/conf.d/upstream.conf')
        ->yml('depends_on')->toBe(['php']);
});

it('adds upstream.conf volume when php service is set', function () {
    $php = new Php();
    $nginx = new Nginx();

    $nginx->phpService($php);

    expect($nginx)->toHaveVolume('./services/nginx/conf.d/upstream.conf', '/etc/nginx/conf.d/upstream.conf');
});

it('sets up the site from env', function (array $env) {
    collect($env)->each(fn ($value, $key) => Env::put(EnvKey::from($key), $value));

    $nginx = new Nginx();

    expect($nginx->sites())
        ->toHaveCount(1)
        ->first()->configuration()->toMatchTextSnapshot();
})->with([
    'default' => fn () => [],
    'custom port' => fn () => [EnvKey::nginx_port->value => 42],
    'with websockets' => fn () => [EnvKey::websocket_enabled->value => 1],
    'ssl' => fn () => [EnvKey::nginx_port->value => 443],
    'ssl with websockets' => fn () => [EnvKey::nginx_port->value => 443, EnvKey::websocket_enabled->value => 1],
]);

it('can set an external certificate folder', function () {
    Storage::disk('cwd')->makeDirectory('certificates');
    Env::put(EnvKey::nginx_port, 443)->put(EnvKey::nginx_external_certificate_folder, 'certificates');

    $nginx = new Nginx();

    expect($nginx)->toHaveVolume('certificates', '/etc/letsencrypt');
});

it('requires external certificate folder to exist', function () {
    Env::put(EnvKey::nginx_port, 443)->put(EnvKey::nginx_external_certificate_folder, 'foo');
    new Nginx();
})->throws(DockerServiceException::class, 'Path [foo] not found on host system');

it('map added site port', function () {
    $nginx = new Nginx();
    $nginx->addSite('foo.ktm', 42);

    expect($nginx)->yml('ports')->toBe(['80:80', '42:42']);
});

it('replaces port 80 from .env', function () {
    Env::put(EnvKey::nginx_port, 81);
    $nginx = new Nginx();

    expect($nginx)->yml('ports')->toBe(['81:81']);
});

it('can enable proxy host not found page', function () {
    $nginx = new Nginx();
    $nginx->enableHostNotFoundPage();

    expect($nginx)->hostNotFoundPageEnabled()->toBeTrue();
});

it('can return its sites', function () {
    expect(new Nginx())
        ->sites()->toHaveCount(1)
        ->getSite('foo.test')
        ->toBeInstanceOf(Site::class)
        ->host()->toBe('foo.test');
});

test('commands', function () {
    expect(new Nginx())->commands()->toBe([NginxRestart::class]);
});

it('publishes assets', function (string $asset, array $env, Closure $setup = null) {
    Env::fake($env);
    Service::fake();

    $nginx = new Nginx();

    if ($setup !== null) {
        call_user_func($setup, $nginx);
    }

    $nginx->publishAssets();

    expect($nginx->assets()->get($asset) ?? '')->toMatchTextSnapshot();
})->with([
    'build/Dockerfile',
    'build/host_not_found.html',
    'sites-available/host_not_found.conf',
    'nginx.conf',
    'conf.d/upstream.conf',
    'sites-available/_foo.com_80.conf',
])->with([
    'default' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'foo.com'],
    'host not found enabled' => [
        'env' => ['RECIPE' => 'test-recipe', 'HOST' => 'foo.com'],
        'setup' => fn () => fn (Nginx $nginx) => $nginx->enableHostNotFoundPage(),
    ],
    'with php service' => [
        'env' => ['RECIPE' => 'test-recipe', 'HOST' => 'foo.com'],
        'setup' => fn () => fn (Nginx $nginx) => $nginx->phpService(new Php()),
    ],
]);
