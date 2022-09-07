<?php

declare(strict_types=1);

use App\Docker\Services\Redis;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Redis())->name()->toBe('redis');
});

it('sets its yml', function () {
    expect(new Redis())->yml()->toMatchSnapshot();
});

it('can set redis version from env', function () {
    Env::put('REDIS_VERSION', '5.4');
    expect(new Redis())->yml('image')->toBe('redis:5.4-alpine');
});

it('adds internal network', function () {
    expect(new Redis())->toHaveNetwork('test-recipe_internal_network');
});

test('commands', function () {
    expect(new Redis())->commands()->toBe([]);
});
