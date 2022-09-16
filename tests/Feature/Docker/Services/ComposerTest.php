<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Composer;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'foo']);
});

it('sets its service name', function () {
    expect(new Composer())->name()->toBe('composer');
});

it('sets its yml', function () {
    expect(new Composer())->yml()->toMatchSnapshot();
});

it('sets its target', function () {
    expect(new Composer())->yml('build.target')->toBe('composer');
});

it('clears its dependencies', function () {
    expect(new Composer())->yml('depends_on')->toBe(null);
});

it('publish assets', function (string $asset, array $env, string $phpVersion) {
    Env::fake($env)->put('PHP_VERSION', $phpVersion);
    Service::fake();

    $composer = new Composer();
    $composer->publishAssets();

    expect($composer->assets()->get($asset) ?? '')->toMatchTextSnapshot();
})->with([
    'build/Dockerfile',
    'build/composer/start_script.sh',
])->with([
    'default' => fn () => ['RECIPE' => 'test-recipe', 'HOST' => 'foo'],
    'pcov' => fn () => ['RECIPE' => 'test-recipe', 'EXTRA_TOOLS' => 'pcov', 'HOST' => 'foo'],
])->with('php versions');

test('commands', function () {
    expect(new Composer())->commands()->toBe([
        \App\Docker\Services\Commands\Composer::class,
    ]);
});
