<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Commands\Npm;
use App\Docker\Services\Node;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'test.ktm']);
});

it('sets its service name', function () {
    expect(new Node())->name()->toBe('node');
});

it('sets its yml', function () {
    expect(new Node())->yml()->toMatchSnapshot();
});

test('default node version', function () {
    expect(new Node())->getNodeVersion()->toBe('lts');
});

it('sets its node version from env', function () {
    Env::put(\App\Enums\EnvKey::node_version, 8);

    expect(new Node())->getNodeVersion()->toBe(8);
});

it('adds internal network', function () {
    expect(new Node())->toHaveNetwork('test_ktm_internal_network');
});

it("doesn't map vite port if in production mode", function () {
    Env::put(\App\Enums\EnvKey::env, 'production');

    expect(new Node())->yml('ports')->toBeEmpty();
});

it('sets its volumes', function () {
    expect(new Node())
        ->toHaveVolume('./src', '/var/www');
});

test('commands', function () {
    expect(new Node())->commands()->toBe([
        \App\Docker\Services\Commands\Node::class,
        Npm::class,
    ]);
});

it('publishes assets', function (string $asset, array $env, Closure $setup = null) {
    Env::fake($env);
    Service::fake();

    $node = new Node();

    if ($setup !== null) {
        call_user_func($setup, $node);
    }

    $node->publishAssets();

    expect($node->assets()->get($asset) ?? '')->toMatchTextSnapshot();
})->with([
    'build/Dockerfile',
])->with([
    'default' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.ktm'],
    'custom version' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'test.ktm', 'NODE_VERSION' => '18'],
]);
