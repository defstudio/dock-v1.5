<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Worker;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Worker())->name()->toBe('worker');
});

it('sets its yml', function () {
    expect(new Worker())->yml()->toMatchSnapshot();
});

it('sets its target', function () {
    expect(new Worker())->yml('build.target')->toBe('worker');
});

test('commands', function () {
    expect(new Worker())->commands()->toBe([]);
});

it('publishes Dockerfile', function (array $env, string $phpVersion) {
    Env::fake($env)->put('PHP_VERSION', $phpVersion);
    Service::fake();

    $worker = new Worker();
    $worker->publishAssets();

    expect($worker->assets()->get('Dockerfile'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
])->with('php versions');

it('publishes start script', function ($env) {
    Env::fake($env);
    Service::fake();

    $worker = new Worker();
    $worker->publishAssets();

    expect($worker->assets()->get('worker/start_script.sh'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
]);
