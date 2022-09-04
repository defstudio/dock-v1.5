<?php

namespace App\Facades;

use App\Testing\FakeTerminal;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed ask(string $question, string|bool $default = null, bool $allowEmpty = false)
 * @method static mixed choose(string $question, array $choices, string|bool $default = null, bool $allowEmpty = false)
 * @method static void render(string $html)
 * @method static void assertAllExpectedMessageSent()
 * @method static void assertSent(string $message)
 */
class Terminal extends Facade
{
    public static function fake(array $questions = []): FakeTerminal
    {
        static::swap($fake = new FakeTerminal($questions));

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'terminal';
    }
}
