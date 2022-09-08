<?php

use App\Docker\Volume;

it('can return host and container paths', function () {
    $volume = new Volume('foo/bar', '/baz/qux');

    expect($volume)
        ->hostPath()->toBe('foo/bar')
        ->containerPath()->toBe('/baz/qux');
});
