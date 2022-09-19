<?php

use App\Enums\EnvKey;
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
        ConfigurationSection::make('first', [ConfigurationOption::make(EnvKey::foo)]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make(EnvKey::bar)]),
    ]));

    invade($bar)->value = 'quuz';

    expect($configuration->get(EnvKey::bar))->toBe('quuz')
        ->and($configuration->get(EnvKey::baz, 'default value'))->toBe('default value');
});

it('can set an option value', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make(EnvKey::foo)]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make(EnvKey::bar), ConfigurationOption::make(EnvKey::baz)]),
    ]));

    $configuration->set(EnvKey::bar, 42);

    expect(invade($bar)->value)->toBe(42);
});

it('can set an extra option value', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make(EnvKey::foo)]),
        ConfigurationSection::make('second', [ConfigurationOption::make(EnvKey::bar)]),
    ]));

    $configuration->set(EnvKey::baz, 42);

    expect($configuration->extraOptions()[EnvKey::baz->value])->toBe(42);
});

it('can write .env file', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make(EnvKey::foo, 'quz')]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make(EnvKey::bar, true)]),
    ]));

    $configuration->set(EnvKey::baz, 42);

    $configuration->writeEnv();

    expect(Storage::disk('cwd')->get('.env'))->toMatchSnapshot();
});

it('can export to array', function () {
    $configuration = new Configuration(collect([
        ConfigurationSection::make('first', [ConfigurationOption::make(EnvKey::foo, 'quz')]),
        ConfigurationSection::make('second', [$bar = ConfigurationOption::make(EnvKey::bar, true)]),
    ]));

    $configuration->set(EnvKey::baz, 42);

    expect($configuration)->toArray()->toMatchSnapshot();
});
