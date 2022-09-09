<?php

declare(strict_types=1);

use App\Docker\Service;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Scheduler;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Scheduler())->name()->toBe('scheduler');
});

it('sets its yml', function () {
    expect(new Scheduler())->yml()->toMatchSnapshot();
});

it('sets its target', function () {
    expect(new Scheduler())->yml('build.target')->toBe('scheduler');
});

test('commands', function () {
    expect(new Scheduler())->commands()->toBe([]);
});

it('publishes Dockerfile', function (array $env, string $phpVersion) {
    Env::fake($env)->put('PHP_VERSION', $phpVersion);
    Service::fake();

    $scheduler = new Scheduler();
    $scheduler->publishAssets();

    expect($scheduler->assets()->get('Dockerfile'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
])->with('php versions');

it('publishes start script', function ($env) {
    Env::fake($env);
    Service::fake();

    $scheduler = new Scheduler();
    $scheduler->publishAssets();

    expect($scheduler->assets()->get('scheduler/start_script.sh'))->toMatchSnapshot();
})->with([
    'default' => fn() => ['RECIPE' => 'test-recipe'],
]);
