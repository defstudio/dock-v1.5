<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

use App\Facades\Terminal;
use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;
use League\Flysystem\Config;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Termwind\Question;
use function Termwind\renderUsing;

it('can make a new option', function(){
    $option = ConfigurationOption::make('foo');
    expect($option)->key()->toBe('foo');
});

it('can be executed if a condition is met', function(){
    $option = new class extends ConfigurationOption{
        public bool $asked = false;

        protected function ask(): void
        {
            $this->asked = true;
        }
    };

    $option->when(false)->setup(new Configuration(collect([])));

    expect($option->asked)->toBeFalse();
});

it('can set its description', function () {
    $option = ConfigurationOption::make('foo')->description('bar');

    expect(invade($option))->description->toBe('bar');
});

it('can set its question', function(){
    $option = ConfigurationOption::make('foo')->question('bar');

    expect(invade($option))->question->toBe('bar');
});

it('can be set as a yes/no question', function () {
    $option = ConfigurationOption::make('foo')->confirm();

    expect(invade($option)->confirm)->toBeTrue()
        ->and(invade($option)->choices)->toBe([true, false]);
});

it('can set its default value', function(){
    $option = ConfigurationOption::make('foo')->default('bar');

    expect(invade($option))->defaultValue->toBe('bar');
});

it('can be set as optional', function () {
    $option = invade(ConfigurationOption::make('foo'));

    expect($option)->required->toBeTrue();

    $option->optional();

    expect($option)->required->toBeFalse();
});

it('can be set as hidden', function () {
    $option = invade(ConfigurationOption::make('foo'));

    expect($option)->hidden->toBeFalse();

    $option->hidden();

    expect($option)->hidden->toBeTrue();
});

it('can set its choices', function () {
    $option = invade(ConfigurationOption::make('foo')->choices(['bar', 'baz']));

    expect($option->choices)->toBe(['bar', 'baz']);
});

it('can handle a custom validation', function () {
    $option = ConfigurationOption::make('foo')->optional();
    invade($option)->value = 1;

    expect(invade($option)->valid(new Configuration(collect())))->toBeTrue();

    $option->validate(fn() => false);

    expect(invade($option)->valid(new Configuration(collect())))->toBeFalse();

    $option->validate(fn() => true);

    expect(invade($option)->valid(new Configuration(collect())))->toBeTrue();
});

it('can execute a callback after set', function () {
    $set = false;

    $option = ConfigurationOption::make('foo')->afterSet(function() use(&$set){
            $set = true;
    });

    invade($option)->notifyValueSet(new Configuration(collect()));

    expect($set)->toBeTrue();
});

it("doesn't set up if is not active", function () {
    $option = new class extends ConfigurationOption{
        protected string $key = 'foo';
        protected string|int|bool $value = 'baz';

        public function ask(): void
        {
            $this->value = 'bar';
        }
    };

    $option->when(fn() => false);

    $option->setup(new Configuration(collect()));

    expect($option->value())->toBe('baz');
});

it('keeps asking a value until valid', function () {
    $option = new class extends ConfigurationOption{
        protected string $key = 'foo';
        protected string|int|bool $value = 0;

        public function ask(): void
        {
            $this->value++;
        }
    };

    $option->validate(fn($value) => $value == 3);

    $option->setup(new Configuration(collect()));

    expect($option->value())->toBe(3);
});

it('normalize its value after set', function () {
    $option = new class extends ConfigurationOption{
        protected string $key = 'foo';
        protected string|int|bool $value;

        public function ask(): void
        {
            $this->value = 'foo';
        }

        protected function normalizeValue(): void
        {
            $this->value = 'bar';
        }
    };

    $option->setup(new Configuration(collect()));

    expect($option->value())->toBe('bar');
});

it('trigger value set callback after set', function () {
    $option = new class extends ConfigurationOption{
        protected string $key = 'foo';
        protected string|int|bool $value;

        public function ask(): void
        {
            $this->value = 'foo';
        }
    };

    $called = false;
    $option->afterSet(function($value) use(&$called){
        $called = true;
        expect($value)->toBe('foo');
    });

    $option->setup(new Configuration(collect()));

    expect($called)->toBeTrue();
});

it('computes its default value', function (string|int|bool $default, string $computed) {
    $option = ConfigurationOption::make('foo')->default($default);
    expect(invade($option)->computeDefaultValue())->toBe($computed);
})->with([
    'true' => ['default' => true, 'computed' => 'yes'],
    'false' => ['default' => false, 'computed' => 'no'],
]);

it('computes the hint text', function(ConfigurationOption $option, string $hint){
    expect(invade($option)->computeHint())->toBe($hint);
})->with([
    'no hint' => [
        'option' => ConfigurationOption::make('foo'),
        'hint' => '',
    ],
    'single hint' => [
        'option' => ConfigurationOption::make('foo')->default('bar'),
        'hint' => "<span class='text-white'>bar</span>",
    ],
    'hit for confirm()' => [
        'option' => ConfigurationOption::make('foo')->confirm(),
        'hint' => "yes, no",
    ],
    'hit for confirm() with default' => [
        'option' => ConfigurationOption::make('foo')->confirm()->default(false),
        'hint' => "yes, <span class='text-white'>no</span>",
    ],
    'hit for choices()' => [
        'option' => ConfigurationOption::make('foo')->choices(['foo', 'bar', 'baz']),
        'hint' => "foo, bar, baz",
    ],
    'hit for choices() with default' => [
        'option' => ConfigurationOption::make('foo')->choices(['foo', 'bar', 'baz'])->default('bar'),
        'hint' => "foo, <span class='text-white'>bar</span>, baz",
    ],
]);

it('computes the question text', function(ConfigurationOption $option, string $text){
    expect(invade($option)->computeQuestion())->toBe($text);
})->with([
    'from question' => [
        'option' => ConfigurationOption::make('foo')->description('quz quuz?')->question('bar baz?'),
        'text' => "<ul class='mx-2'><li><span class='text-green'>bar baz?</span><span class='text-green'>:</span></li></ul>",
    ],
    'from description' => [
        'option' => ConfigurationOption::make('foo')->description('quz quuz?'),
        'text' => "<ul class='mx-2'><li><span class='text-green'>quz quuz?</span><span class='text-green'>:</span></li></ul>",
    ],
    'with hint' => [
        'option' => ConfigurationOption::make('foo')->description('quz quuz?')->confirm()->default(true),
        'text' => "<ul class='mx-2'><li><span class='text-green'>quz quuz?</span> <span class='text-gray'>[<span class='text-white'>yes</span>, no]</span><span class='text-green'>:</span></li></ul>",
    ],
    'with optional default' => [
        'option' => ConfigurationOption::make('foo')->description('quz quuz?')->default('baz')->optional(),
        'text' => "<ul class='mx-2'><li><span class='text-green'>quz quuz?</span> <span class='text-gray'>[<span class='text-white'>baz</span>]</span> <span class='text-gray'>(press 'x' to skip)</span><span class='text-green'>:</span></li></ul>",
    ],
]);

it('normalize its value', function (ConfigurationOption $option, string|int|bool $value, string|int|bool $normalized) {
    invade($option)->value = $value;
    invade($option)->normalizeValue();
    expect(invade($option)->value)->toBe($normalized);
})->with([
    'default value' => [
        'option' => ConfigurationOption::make('foo')->default('bar'),
        'value' => '', 'normalized' => 'bar',
    ],
    'x' => [
        'option' => ConfigurationOption::make('foo'),
        'value' => 'x', 'normalized' => 'x'
    ],
    'X' => [
        'option' => ConfigurationOption::make('foo'),
        'value' => 'X', 'normalized' => 'X'
    ],
    'x with optional and default value' => [
        'option' => ConfigurationOption::make('foo')->optional()->default('foo'),
        'value' => 'x', 'normalized' => ''
    ],
    'X with optional and default value' => [
        'option' => ConfigurationOption::make('foo')->optional()->default('foo'),
        'value' => 'X', 'normalized' => ''
    ],
    'foo with optional and default value' => [
        'option' => ConfigurationOption::make('bar')->optional()->default('baz'),
        'value' => 'foo', 'normalized' => 'foo'
    ],
    'foo' => [
        'option' => ConfigurationOption::make('bar'),
        'value' => 'foo', 'normalized' => 'foo'
    ],
    'true' => [
        'option' => ConfigurationOption::make('bar'),
        'value' => true, 'normalized' => true
    ],
    'false' => [
        'option' => ConfigurationOption::make('bar'),
        'value' => false, 'normalized' => false
    ],
    'yes' => [
        'option' => ConfigurationOption::make('bar'),
        'value' => 'yes', 'normalized' => 'yes'
    ],
    'no' => [
        'option' => ConfigurationOption::make('bar'),
        'value' => 'no', 'normalized' => 'no'
    ],
    'yes for confirm' => [
        'option' => ConfigurationOption::make('bar')->confirm(),
        'value' => 'yes', 'normalized' => true
    ],
    'no for confirm' => [
        'option' => ConfigurationOption::make('bar')->confirm(),
        'value' => 'no', 'normalized' => false
    ],
]);

it('prompts the question', function(ConfigurationOption $option, string $rendered){
    Terminal::fake();

    invade($option)->ask();

    Terminal::assertSent($rendered);
})->with([
    'plain question' => [
        'option' => ConfigurationOption::make('foo')->question('bar baz?'),
        'rendered' => "bar baz?",
    ],
    'question with default value' => [
        'option' => ConfigurationOption::make('foo')->question('bar baz?')->default('quuz'),
        'rendered' => "bar baz? [quuz]:",
    ],
    'question with choices' => [
        'option' => ConfigurationOption::make('foo')->question('bar baz?')->choices(['quuz', 'quz']),
        'rendered' => "bar baz? [quuz, quz]:",
    ],
    'question with choices and default' => [
        'option' => ConfigurationOption::make('foo')->question('bar baz?')->choices(['quuz', 'quz'])->default('quz'),
        'rendered' => "bar baz? [quuz, quz]",
    ],
    'question with optional default' => [
        'option' => ConfigurationOption::make('foo')->question('bar baz?')->default('quuz')->optional(),
        'rendered' => "bar baz? [quuz] (press 'x' to skip)",
    ],
])->only();

it('validate answer', function (ConfigurationOption $option, string|int|bool $value, bool $valid, string $message = null) {
    $output = fakeOutput();

    invade($option)->value = $value;

    expect(invade($option)->valid(new Configuration(collect())))->toBe($valid);

    if(!empty($message)){
        expect($output->message)->toBe("<fg=red;options=bold>Error:</> $message");
    }
})->with([
    'valid' => [
        'option' => ConfigurationOption::make('foo'),
        'value' => 'bar',
        'valid' => true
    ],
    'not required' => [
        'option' => ConfigurationOption::make('foo')->optional(),
        'value' => '',
        'valid' => true
    ],
    'missing' => [
        'option' => ConfigurationOption::make('foo'),
        'value' => '',
        'valid' => false,
        'message' => 'A value is required',
    ],
    'invalid' => [
        'option' => ConfigurationOption::make('foo')->choices(['foo', 'bar']),
        'value' => 'baz',
        'valid' => false,
        'message' => '[baz] is not a valid value',
    ],
    'invalid from closure' => [
        'option' => ConfigurationOption::make('foo')
            ->choices(['foo', 'bar'])
            ->validate(fn() => false),
        'value' => 'baz',
        'valid' => false,
        'message' => '[baz] is not a valid value',
    ],
    'invalid from closure with custom message' => [
        'option' => ConfigurationOption::make('foo')
            ->validate(fn($value) => "$value is absolutely wrong"),
        'value' => 'baz',
        'valid' => false,
        'message' => 'baz is absolutely wrong',
    ],
]);

it('checks if is active', function(ConfigurationOption $option, bool $active){
    expect(invade($option)->isActive(new Configuration(collect())))->toBe($active);
})->with([
    'default' => [
        'option' => ConfigurationOption::make('foo'),
        'active' => true,
    ],
    'active with bool' => [
        'option' => ConfigurationOption::make('foo')->when(true),
        'active' => true,
    ],
    'inactive with bool' => [
        'option' => ConfigurationOption::make('foo')->when(false),
        'active' => false,
    ],
    'active with closure' => [
        'option' => ConfigurationOption::make('foo')->when(fn() => true),
        'active' => true,
    ],
    'inactive with closure' => [
        'option' => ConfigurationOption::make('foo')->when(fn() => false),
        'active' => false,
    ],
]);

it('returns its key', function () {
    $option = ConfigurationOption::make('BAR');

    expect($option->key())->toBe('BAR');
});

it('returns its value', function () {
    $option = ConfigurationOption::make('foo');

    expect($option->value())->toBe('');

    invade($option)->value = 'bar';

    expect($option->value())->toBe('bar');
});
