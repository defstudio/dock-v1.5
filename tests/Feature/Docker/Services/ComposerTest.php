<?php
declare(strict_types=1);

use App\Docker\Services\Composer;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
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

test('commands', function () {
    expect(new Composer())->commands()->toBe([
        \App\Docker\Services\Commands\Composer::class,
    ]);
});
