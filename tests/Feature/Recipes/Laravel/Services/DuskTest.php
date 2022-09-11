<?php

declare(strict_types=1);

use App\Docker\Services\Nginx;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Dusk;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'test.ktm']);
});

it('sets its service name', function () {
    expect(new Dusk())->name()->toBe('dusk');
});

it('sets its yml', function () {
    expect(new Dusk())->yml()->toMatchSnapshot();
});

it('sets its volumes', function () {
    expect(new Dusk())
        ->toHaveVolume('./src', '/var/www');
});

it('adds internal network', function () {
    expect(new Dusk())->toHaveNetwork('test.ktm_internal_network');
});

it('add a link to nginx service', function () {
    $dusk = new Dusk();
    $dusk->nginxService(new Nginx());

    expect($dusk)->yml('links')->toContain('nginx:test.ktm');
});

test('commands', function () {
    expect(new Dusk())->commands()->toBe([]);
});
