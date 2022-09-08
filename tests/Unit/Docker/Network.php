<?php

use App\Docker\Network;

it('can return its name', function () {
    expect(new Network('foo'))
        ->name()->toBe('foo');
});
