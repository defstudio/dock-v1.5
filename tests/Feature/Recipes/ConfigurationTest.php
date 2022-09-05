<?php

use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;
use App\Recipes\ConfigurationSection;

it('can setup its options', function () {
    $option = new class extends ConfigurationOption
    {
        public bool $called = false;

        public function configure(Configuration $configuration): void
        {
            $this->called = true;
        }
    };

    $configuration = new Configuration(collect([
        ConfigurationSection::make('test', [$option]),
    ]));

    $configuration->configure();

    expect($option->called)->toBeTrue();
});

it('can find an option value', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make('FOO')]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make('BAR'), ConfigurationOption::make('BAZ')]),
    ]));

    invade($bar)->value = 'quuz';

    expect($configuration->get('BAR'))->toBe('quuz')
        ->and($configuration->get('QUUZ', 'default value'))->toBe('default value');
});

it('can set an option value', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make('FOO')]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make('BAR'), ConfigurationOption::make('BAZ')]),
    ]));

    $configuration->set('BAR', 42);

    expect(invade($bar)->value)->toBe(42);
});

it('can set an extra option value', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make('FOO')]),
        ConfigurationSection::make('second', [ConfigurationOption::make('BAR'), ConfigurationOption::make('BAZ')]),
    ]));

    $configuration->set('QUUZ', 42);

    expect($configuration->extraOptions()['QUUZ'])->toBe(42);
});
