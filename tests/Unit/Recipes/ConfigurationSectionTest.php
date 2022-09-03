<?php

use App\Recipes\ConfigurationOption;
use App\Recipes\ConfigurationSection;

it('returns its name', function(){
    $section = ConfigurationSection::make('foo', []);

    expect($section)->name()->toBe('foo');
});

it('returns its options', function () {
    $section = ConfigurationSection::make('foo', [ConfigurationOption::make('bar')]);

    expect($section->options())->first()->key()->toBe('bar');
});
