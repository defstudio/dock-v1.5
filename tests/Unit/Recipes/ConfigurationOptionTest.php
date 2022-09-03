<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

use App\Recipes\Configuration;
use App\Recipes\ConfigurationOption;

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

it('computes its default value', function () {
    $option = ConfigurationOption::make('foo')->default(true);
    expect(invade($option)->computeDefaultValue())->toBe('yes');

    $option = ConfigurationOption::make('foo')->default(false);
    expect(invade($option)->computeDefaultValue())->toBe('no');
});

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
