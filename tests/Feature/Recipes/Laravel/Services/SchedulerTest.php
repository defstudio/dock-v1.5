<?php

declare(strict_types=1);

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
