<?php

/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

use App\Enums\EnvKey;
use App\Facades\Terminal;
use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;

it('can make a new option', function () {
    $option = ConfigurationOption::make(EnvKey::recipe);
    expect($option)->key()->toBe(EnvKey::recipe);
});

it('can be executed if a condition is met', function () {
    $option = new class extends ConfigurationOption
    {
        public bool $asked = false;

        protected function ask(Configuration $configuration): void
        {
            $this->asked = true;
        }
    };

    $option->when(false)->configure(new Configuration(collect([])));

    expect($option->asked)->toBeFalse();
});

it('can set its description', function () {
    $option = ConfigurationOption::make(EnvKey::foo)->description('bar');

    expect(invade($option))->description->toBe('bar');
});

it('can set its question', function () {
    $option = ConfigurationOption::make(EnvKey::foo)->question('bar');

    expect(invade($option))->question->toBe('bar');
});

it('can be set as a yes/no question', function () {
    $option = ConfigurationOption::make(EnvKey::foo)->confirm();

    expect(invade($option)->confirm)->toBeTrue()
        ->and(invade($option)->choices)->toBe([true, false]);
});

it('can set its default value', function () {
    $option = ConfigurationOption::make(EnvKey::foo)->default('bar');

    expect(invade($option))->defaultValue->toBe('bar');
});

it('can set its default value closure', function () {
    $option = ConfigurationOption::make(EnvKey::foo)->default(fn () => 'bar');

    expect(invade($option))->defaultValue->toBeInstanceOf(Closure::class);
});

it('can be set as optional', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo));

    expect($option)->required->toBeTrue();

    $option->optional();

    expect($option)->required->toBeFalse()
        ->exportIfEmpty->toBeFalse();
});

it('can be set as optional and to export if empty', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo));

    expect($option)->required->toBeTrue();

    $option->optional(true);

    expect($option)->required->toBeFalse()
        ->exportIfEmpty->toBeTrue();
});

it('can be set as hidden', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo));

    expect($option)->hidden->toBeFalse();

    $option->hidden();

    expect($option)->hidden->toBeTrue();
});

it('can set its choices', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo)->choices(['bar', 'baz']));

    expect($option->choices)->toBe(['bar', 'baz'])
        ->and($option->multiple)->toBeFalse();
});

it('can set its choices as closure', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo)->choices(fn () => ['bar', 'baz']));

    expect($option->choices)->toBeInstanceOf(Closure::class)
        ->and($option->multiple)->toBeFalse();
});

it('can set its choices with multiple selection', function () {
    $option = invade(ConfigurationOption::make(EnvKey::foo)->choices(['bar', 'baz'], true));

    expect($option->choices)->toBe(['bar', 'baz'])
        ->and($option->multiple)->toBeTrue();
});

it('can handle a custom validation', function () {
    Terminal::fake();

    $option = ConfigurationOption::make(EnvKey::foo)->optional();
    invade($option)->value = 1;

    expect(invade($option)->valid(new Configuration(collect())))->toBeTrue();

    $option->validate(fn () => false);

    expect(invade($option)->valid(new Configuration(collect())))->toBeFalse();

    $option->validate(fn () => true);

    expect(invade($option)->valid(new Configuration(collect())))->toBeTrue();
});

it('can execute a callback after set', function () {
    $set = false;

    $option = ConfigurationOption::make(EnvKey::foo)->afterSet(function () use (&$set) {
        $set = true;
    });

    invade($option)->notifyValueSet(new Configuration(collect()));

    expect($set)->toBeTrue();
});

it("doesn't set up if is not active", function () {
    $option = new class extends ConfigurationOption
    {
        protected EnvKey $key = EnvKey::foo;

        protected string|int|bool $value = 'baz';

        public function ask(Configuration $configuration): void
        {
            $this->value = 'bar';
        }
    };

    $option->when(fn () => false);

    $option->configure(new Configuration(collect()));

    expect($option->value())->toBe('baz');
});

it('keeps asking a value until valid', function () {
    Terminal::fake();
    $option = new class extends ConfigurationOption
    {
        protected EnvKey $key = EnvKey::foo;

        protected string|int|bool $value;

        public function ask(Configuration $configuration): void
        {
            $this->value ??= 0;
            $this->value++;
        }
    };

    $option->validate(fn ($value) => $value == 3);

    $option->configure(new Configuration(collect()));

    expect($option->value())->toBe(3);
});

it('normalize its value after set', function () {
    $option = new class extends ConfigurationOption
    {
        protected EnvKey $key = EnvKey::foo;

        protected string|int|bool $value;

        public function ask(Configuration $configuration): void
        {
            $this->value = 'foo';
        }

        protected function normalizeValue(Configuration $configuration): void
        {
            $this->value = 'bar';
        }
    };

    $option->configure(new Configuration(collect()));

    expect($option->value())->toBe('bar');
});

it('trigger value set callback after set', function () {
    $option = new class extends ConfigurationOption
    {
        protected EnvKey $key = EnvKey::foo;

        protected string|int|bool $value;

        public function ask(Configuration $configuration): void
        {
            $this->value = 'foo';
        }
    };

    $called = false;
    $option->afterSet(function ($value) use (&$called) {
        $called = true;
        expect($value)->toBe('foo');
    });

    $option->configure(new Configuration(collect()));

    expect($called)->toBeTrue();
});

it('computes its default value', function (string|int|bool $default, string $computed) {
    $option = ConfigurationOption::make(EnvKey::foo)->default($default);
    expect(invade($option)->computeDefaultValue(new Configuration(collect())))->toBe($computed);
})->with([
    'closure' => ['default' => fn () => 'foo', 'computed' => 'foo'],
    'true' => ['default' => true, 'computed' => 'yes'],
    'false' => ['default' => false, 'computed' => 'no'],
]);

it('computes its choices', function (array|Closure $choice, array $computed) {
    $option = ConfigurationOption::make(EnvKey::foo)->choices($choice);
    expect(invade($option)->computeChoices(new Configuration(collect())))->toBe($computed);
})->with([
    'closure' => ['choices' => fn () => ['foo', 'bar'], 'computed' => ['foo', 'bar']],
    'booleans' => ['choices' => [true, false], 'computed' => ['yes', 'no']],
    'strings' => ['choices' => ['foo', 'bar', 'baz'], 'computed' => ['foo', 'bar', 'baz']],
]);

it('normalize its value', function (ConfigurationOption $option, string|int|bool $value, string|int|bool $normalized) {
    invade($option)->value = $value;
    invade($option)->normalizeValue(new Configuration(collect()));
    expect(invade($option)->value)->toBe($normalized);
})->with([
    'default value' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->default('bar'),
        'value' => '', 'normalized' => 'bar',
    ],
    'closure default' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->default(fn () => 'bar'),
        'value' => '', 'normalized' => 'bar',
    ],
    'x' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'x', 'normalized' => 'x',
    ],
    'X' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'X', 'normalized' => 'X',
    ],
    'x with optional and default value' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->optional()->default('foo'),
        'value' => 'x', 'normalized' => '',
    ],
    'X with optional and default value' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->optional()->default('foo'),
        'value' => 'X', 'normalized' => '',
    ],
    'foo with optional and default value' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->optional()->default('baz'),
        'value' => 'foo', 'normalized' => 'foo',
    ],
    'foo' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'foo', 'normalized' => 'foo',
    ],
    'true' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => true, 'normalized' => true,
    ],
    'false' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => false, 'normalized' => false,
    ],
    'yes' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'yes', 'normalized' => 'yes',
    ],
    'no' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'no', 'normalized' => 'no',
    ],
    'yes for confirm' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->confirm(),
        'value' => 'yes', 'normalized' => 'yes',
    ],
    'no for confirm' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->confirm(),
        'value' => 'no', 'normalized' => 'no',
    ],
]);

it('prompts the question', function (ConfigurationOption $option, string $rendered) {
    Terminal::fake();

    invade($option)->ask(new Configuration(collect()));

    Terminal::assertSent($rendered);
})->with([
    'question' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->question('bar baz?'),
        'rendered' => 'bar baz?',
    ],
    'question with choices' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->question('bar baz?')->choices(['quuz', 'quz']),
        'rendered' => 'bar baz?',
    ],
]);

it('prompts multiple choice', function () {
    Terminal::fake([
        'bar baz?' => 'quuz',
        '<2>bar baz?' => 'quz',
        '<3>bar baz?' => '',
    ]);

    $option = ConfigurationOption::make(EnvKey::foo)->question('bar baz?')->choices(['quuz', 'quz', 'zap'], true);

    invade($option)->ask(new Configuration(collect()));

    Terminal::assertAllExpectedMessageSent();
});

it('validate answer', function (ConfigurationOption $option, string|int|bool $value, bool $valid, string $message = null) {
    Terminal::fake();

    invade($option)->value = $value;

    expect(invade($option)->valid(new Configuration(collect())))->toBe($valid);

    if (!empty($message)) {
        Terminal::assertSent("Error: $message");
    }
})->with([
    'valid' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => 'bar',
        'valid' => true,
    ],
    'not required' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->optional(),
        'value' => '',
        'valid' => true,
    ],
    'missing' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'value' => '',
        'valid' => false,
        'message' => 'A value is required',
    ],
    'invalid' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->choices(['foo', 'bar']),
        'value' => 'baz',
        'valid' => false,
        'message' => '[baz] is not a valid value',
    ],
    'invalid from closure' => [
        'option' => ConfigurationOption::make(EnvKey::foo)
            ->choices(['foo', 'bar'])
            ->validate(fn () => false),
        'value' => 'baz',
        'valid' => false,
        'message' => '[baz] is not a valid value',
    ],
    'invalid from closure with custom message' => [
        'option' => ConfigurationOption::make(EnvKey::foo)
            ->validate(fn ($value) => "$value is absolutely wrong"),
        'value' => 'baz',
        'valid' => false,
        'message' => 'baz is absolutely wrong',
    ],
]);

it('checks if is active', function (ConfigurationOption $option, bool $active) {
    expect(invade($option)->isActive(new Configuration(collect())))->toBe($active);
})->with([
    'default' => [
        'option' => ConfigurationOption::make(EnvKey::foo),
        'active' => true,
    ],
    'active with bool' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->when(true),
        'active' => true,
    ],
    'inactive with bool' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->when(false),
        'active' => false,
    ],
    'active with closure' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->when(fn () => true),
        'active' => true,
    ],
    'inactive with closure' => [
        'option' => ConfigurationOption::make(EnvKey::foo)->when(fn () => false),
        'active' => false,
    ],
]);

it('returns its key', function () {
    $option = ConfigurationOption::make(EnvKey::websocket_port);

    expect($option->key())->toBe(EnvKey::websocket_port);
});

it('returns its value', function () {
    $option = ConfigurationOption::make(EnvKey::foo);

    expect($option->value())->toBe('');

    invade($option)->value = 'bar';

    expect($option->value())->toBe('bar');
});
