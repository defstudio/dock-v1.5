<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Testing;

use App\Terminal\Terminal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertTrue;
use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

class FakeTerminal extends Terminal
{
    private bool $fakeAll = false;

    private array $sentMessages = [];

    private array $sentHtmlMessages = [];

    private array $ranCommands = [];

    private array $ranCommandsInShell = [];

    public function __construct(private array $messages, private readonly array $commands)
    {
        $this->messages = collect($this->messages)
            ->mapWithKeys(fn ($value, $key) => is_int($key)
                ? [$value => null]
                : [$key => $value])->toArray();

        if (empty($this->messages)) {
            $this->fakeAll = true;
        }

        parent::__construct();
    }

    public function run(array $command, array $env = []): int
    {
        $this->ranCommands[] = [
            'command' => $command,
            'env' => $env,
        ];

        return $this->commands[implode(' ', $command)] ?? 0;
    }

    public function runAndReturnOutput(array $command, array $env = []): string
    {
        $this->ranCommands[] = [
            'command' => $command,
            'env' => $env,
        ];

        return $this->commands[implode(' ', $command)] ?? '';
    }

    public function runInShell(array $command, array $env = []): int
    {
        $this->ranCommandsInShell[] = [
            'command' => $command,
            'env' => $env,
        ];

        return $this->commands[implode(' ', $command)] ?? 0;
    }

    public function runInShellAndReturnOutput(array $command, array $env = []): string
    {
        $this->ranCommandsInShell[] = [
            'command' => $command,
            'env' => $env,
        ];

        return $this->commands[implode(' ', $command)] ?? '';
    }

    public function ask(string $question, bool|string $default = null, bool $allowEmpty = false): mixed
    {
        return $this->handle($question);
    }

    public function choose(string $question, array $choices, string $default = null, bool|string $allowEmpty = false): mixed
    {
        return $this->handle($question);
    }

    public function render(string $html): void
    {
        $this->handle($html);
    }

    private function handle(string $message): mixed
    {
        $this->sentHtmlMessages[] = $message;

        $message = Str::of($message)->stripTags()->squish()->toString();
        $this->sentMessages[] = $message;

        if ($this->fakeAll) {
            return 'foo';
        }

        if (empty($this->messages)) {
            assertNotEmpty($this->messages, "Unexpected message [$message]");
        }

        $nextMessageKey = array_key_first($this->messages);

        $nextMessage = Str::of($nextMessageKey)->stripTags()->squish()->toString();

        assertEquals($nextMessage, $message, "Unexpected message [$message]. Next message should be [$nextMessage]");

        $answer = $this->messages[$nextMessageKey];
        unset($this->messages[$nextMessageKey]);

        return $answer;
    }

    public function assertAllExpectedMessageSent(): void
    {
        assertEmpty($this->messages, 'Failed asserting all messages were sent');
    }

    public function assertSentMessagesMatchSnapshot(): void
    {
        assertMatchesHtmlSnapshot(implode('<br><br><hr><br><br>', $this->sentHtmlMessages));
    }

    public function assertSent(string $message): void
    {
        $message = Str::of($message)->stripTags()->squish()->toString();

        $count = count($this->sentMessages);
        assertContains($message, $this->sentMessages, "Failed to assert [$message] was sent. (sent $count messages so far).");
    }

    public function assertSentHtml(string $html): void
    {
        $count = count($this->sentHtmlMessages);
        assertContains($html, $this->sentHtmlMessages, "Failed to assert [$html] was sent. (sent $count messages so far).");
    }

    public function assertRan(array|string $command, array $env = null): void
    {
        if (is_string($command)) {
            $command = explode(' ', $command);
        }

        $sent = collect($this->ranCommands)
            ->filter(fn (array $ranCommand) => $ranCommand['command'] === $command)
            ->when($env !== null, fn (Collection $ranCommands) => $ranCommands->filter(fn (array $ranCommand) => $ranCommand['env'] === $env))
            ->isNotEmpty();

        $command = implode(' ', $command);
        $count = count($this->ranCommands);

        if ($env === null) {
            assertTrue($sent, "Failed to assert [$command] command was run. (ran $count commands so far).");
        } else {
            $env = collect($env)->map(fn ($value, $key) => "$key=$value")->join(', ');
            assertTrue($sent, "Failed to assert [$command] command was run with env [$env]. (ran $count commands so far).");
        }
    }

    public function assertRanInShell(array|string $command, array $env = null): void
    {
        if (is_string($command)) {
            $command = explode(' ', $command);
        }

        $sent = collect($this->ranCommandsInShell)
            ->filter(fn (array $ranCommand) => $ranCommand['command'] === $command)
            ->when($env !== null, fn (Collection $ranCommands) => $ranCommands->filter(fn (array $ranCommand) => $ranCommand['env'] === $env))
            ->isNotEmpty();

        $command = implode(' ', $command);
        $count = count($this->ranCommandsInShell);

        if ($env === null) {
            assertTrue($sent, "Failed to assert [$command] command was run in shell. (ran $count commands so far).");
        } else {
            $env = collect($env)->map(fn ($value, $key) => "$key=$value")->join(', ');
            assertTrue($sent, "Failed to assert [$command] command was run in shell with env [$env]. (ran $count commands so far).");
        }
    }

    public function dumpRanCommands(): void
    {
        dump($this->ranCommands);
    }

    public function dumpSentMessages(): void
    {
        dump($this->sentMessages);
    }
}
