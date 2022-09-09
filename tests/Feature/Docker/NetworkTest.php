<?php

use App\Docker\Network;

it('can return its name', function () {
    expect(new Network('foo'))
        ->name()->toBe('foo');
});

it('can be set as external', function () {
    $network = new Network('bar');
    $network->external();

    expect($network)->toArray()->toBe(['external' => true]);
});

it('prints its setup', function () {
    $network = new Network('baz');

    expect($network)->toArray()->toBe(['name' => 'baz', 'driver' => 'bridge']);
});
