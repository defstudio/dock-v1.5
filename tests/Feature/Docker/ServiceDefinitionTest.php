<?php

beforeEach(function () {
    $this->definition = new \App\Docker\ServiceDefinition([
        'foo' => 'bar',
        'baz' => [
            'quuz' => 'quz',
        ],
    ]);
});

it('can set a value', function () {
    $this->definition->set('zip.zap', 'puck');

    expect($this->definition->toArray())->toMatchSnapshot();
});

it('can retrieve a value', function () {
    expect($this->definition)->get('baz.quuz')->toBe('quz');
});

it('can push a value into an array', function () {
    $this->definition->push('baz.quuz', 'xyzzy');
    $this->definition->push('thud.waldo', 'corge');

    expect($this->definition->toArray())->toMatchSnapshot();
});

it('can unset a value', function () {
    $this->definition->unset('baz.quuz');

    expect($this->definition->toArray())->toMatchSnapshot();
});
