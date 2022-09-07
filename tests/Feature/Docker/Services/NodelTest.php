<?php
declare(strict_types=1);

use App\Docker\Services\Commands\Npm;
use App\Docker\Services\MySql;
use App\Docker\Services\Node;
use App\Docker\Volume;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe']);
});

it('sets its service name', function () {
    expect(new Node())->name()->toBe('node');
});

it('sets its yml', function () {
    expect(new Node())->yml()->toMatchSnapshot();
});

test('default node version', function (){
   expect(new Node())->getNodeVersion()->toBe('lts');
});

it('sets its node version from env', function(){
    Env::put('NODE_VERSION', 8);

    expect(new Node())->getNodeVersion()->toBe(8);
});

it('adds internal network', function () {
    expect(new Node())->toHaveNetwork('test-recipe_internal_network');
});

it("doesn't map vite port if in production mode", function(){
    Env::put('ENV', 'production');

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
