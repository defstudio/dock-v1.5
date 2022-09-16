<?php

declare(strict_types=1);

use App\Docker\Services\Redis;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'test.com']);
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

it('can enable persistence from env', function () {
    Env::put('REDIS_PERSIST_DATA', 1);
    expect(new Redis())->yml('command')->toBe('redis-server --loglevel warning --save 60 1');
});

it('can customize persistence configs from env', function () {
    Env::put('REDIS_PERSIST_DATA', 1);
    Env::put('REDIS_SNAPSHOT_EVERY_SECONDS', 42);
    Env::put('REDIS_SNAPSHOT_EVERY_WRITES', 18);
    expect(new Redis())->yml('command')->toBe('redis-server --loglevel warning --save 42 18');
});

it('adds internal network', function () {
    expect(new Redis())->toHaveNetwork('test_com_internal_network');
});

it('sets its volumes', function () {
    Env::put('REDIS_PERSIST_DATA', 1);
    expect(new Redis())
        ->toHaveVolume('./volumes/redis/data', '/data');
});

test('commands', function () {
    expect(new Redis())->commands()->toBe([]);
});
