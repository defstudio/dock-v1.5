<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Scheduler;
use App\Recipes\Laravel\Services\Websocket;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Websocket())->name()->toBe('websocket');
});

it('sets its yml', function () {
    expect(new Websocket())->yml()->toMatchSnapshot();
});

it('sets its target', function () {
    expect(new Websocket())->yml('build.target')->toBe('websocket');
});

it('can add php service dependency', function () {
    expect(new Websocket())->yml('depends_on')->toBe(['php']);
});

it('can add reverse proxy network', function () {
    Env::put('REVERSE_PROXY_NETWORK', 'foo-network');

    expect(new Websocket())
        ->toHaveNetwork('foo-network')
        ->getNetworks()->get('foo-network')->toArray()->toBe(['external' => true]);
});

test('commands', function () {
    expect(new Websocket())->commands()->toBe([]);
});

it('publishes assets', function (string $asset, array $env, string $phpVersion) {
    Env::fake($env)->put('PHP_VERSION', $phpVersion);
    Service::fake();

    $scheduler = new Scheduler();
    $scheduler->publishAssets();

    expect($scheduler->assets()->get($asset))->toMatchSnapshot();
})->with([
    'build/Dockerfile',
    'build/websocket/start_script.sh',
])->with([
    'default' => fn () => ['RECIPE' => 'test-recipe'],
])->with('php versions');
