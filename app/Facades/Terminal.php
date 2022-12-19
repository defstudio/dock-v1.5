<?php

declare(strict_types=1);

namespace App\Facades;

use App\Testing\FakeTerminal;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int run(array $command, array $env = [])
 * @method static int runInShell(array $command, array $env = [])
 * @method static string runAndReturnOutput(array $command, array $env = [])
 * @method static mixed ask(string $question, string|bool $default = null, bool $allowEmpty = false)
 * @method static mixed choose(string $question, array $choices, string|bool $default = null, bool|string $allowEmpty = false)
 * @method static void render(string $html)
 * @method static void titleBanner(string $title)
 * @method static void failureBanner(string $message)
 * @method static void successBanner(string $message)
 * @method static void error(string $message)
 * @method static void assertAllExpectedMessageSent()
 * @method static void assertSent(string $message)
 * @method static void assertSentMessagesMatchSnapshot(): void
 * @method static void assertSentHtml(string $html)
 * @method static void assertRan(array|string $command, array $env = null)
 * @method static void assertRanInShell(array|string $command, array $env = null)
 * @method static void dumpRanCommands()
 * @method static void dumpSentMessages()
 */
class Terminal extends Facade
{
    public static function fake(array $questions = [], array $commands = []): FakeTerminal
    {
        static::swap($fake = new FakeTerminal($questions, $commands));

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'terminal';
    }
}
