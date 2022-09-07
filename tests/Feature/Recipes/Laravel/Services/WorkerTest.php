<?php
declare(strict_types=1);

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
