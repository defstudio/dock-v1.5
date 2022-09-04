<?php

namespace App\Testing;

use App\Termwind\Terminal;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\QuestionHelper as SymfonyQuestionHelper;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;

class FakeTerminal extends Terminal
{
    private bool $fakeAll = false;
    private array $sentMessages =  [];

    public function __construct(private array $messages)
    {
        $this->messages = collect($this->messages)
            ->mapWithKeys(fn ($value, $key) => is_int($key)
                ? [$value => null]
                : [$key => $value])->toArray();

        if(empty($this->messages)){
            $this->fakeAll = true;
        }

        parent::__construct();
    }

    public function ask(string $question, bool|string $default = null, bool $allowEmpty = false): mixed
    {
        return $this->handle($question);
    }

    public function choose(string $question, array $choices, bool|string $default = null, bool $allowEmpty = false): mixed
    {
        return $this->handle($question);
    }

    public function render(string $html): void
    {
        $this->handle($html);
    }

    private function handle(string $message): mixed
    {
        $message = Str::of($message)->stripTags()->squish()->toString();

        $this->sentMessages[] = $message;

        if($this->fakeAll){
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
        assertEmpty($this->messages, "Failed asserting all messages were sent");
    }

    public function assertSent(string $message): void
    {
        $message = Str::of($message)->stripTags()->squish()->toString();

        $count = count($this->sentMessages);
        assertContains($message, $this->sentMessages, "Failed to assert [$message] was sent. (sent $count messages so far).");
    }
}
