<?php

/** @noinspection PhpUnused */
/** @noinspection PhpInternalEntityUsedInspection */
declare(strict_types=1);

namespace App\Terminal;

use Closure;
use Symfony\Component\Console\Helper\QuestionHelper as SymfonyQuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question as SymfonyQuestion;
use Symfony\Component\Process\Process;
use Termwind\Helpers\QuestionHelper;
use Termwind\HtmlRenderer;
use Termwind\Termwind;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Terminal
{
    /**
     * The streamable input to receive the input from the user.
     */
    private static ?StreamableInputInterface $streamableInput;

    /**
     * Gets the streamable input implementation.
     */
    public static function getStreamableInput(): StreamableInputInterface
    {
        return self::$streamableInput ??= new ArgvInput();
    }

    public function render(string $html): void
    {
        (new HtmlRenderer())->render($html, OutputInterface::OUTPUT_NORMAL);
    }

    public function successBanner(string $message): void
    {
        $messageLength = max(strlen($message) + 4, 50);
        $this->render("<div class='mx-1 mt-1 pt-1 px-1 min-w-$messageLength bg-green text-black text-center'>SUCCESS!</div>");
        $this->render("<div class='mx-1 mb-1 p-1 min-w-$messageLength bg-green text-black text-center'>$message</div>");
    }

    public function titleBanner(string $title): void
    {
        $titleLength = max(strlen($title) + 4, 50);
        $this->render("<div class='mx-1 mt-1 py-1 px-1 min-w-$titleLength bg-cyan text-black text-center'>$title</div>");
    }

    public function failureBanner(string $message): void
    {
        $messageLength = max(strlen($message) + 4, 50);
        $this->render("<div class='mx-1 mt-1 pt-1 px-1 min-w-$messageLength bg-red text-black text-center'>FAILURE!</div>");
        $this->render("<div class='mx-1 mb-1 p-1 min-w-$messageLength bg-red text-black text-center'>$message</div>");
    }

    public function error(string $message): void
    {
        $this->render("<div class='mx-5 mb-1'><span class='text-red font-bold'>Error:</span> $message");
    }

    private function makeProcess(array $command, array $env = [], bool $tty = true): Process
    {
        $process = new Process(command: $command, env: $env);
        $process->setTty($tty && Process::isTtySupported());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        return $process;
    }

    private function makeShell(array $command, array $env = [], bool $tty = true): Process
    {
        $process = Process::fromShellCommandline(implode(' ', $command), env: $env);
        $process->setTty($tty && Process::isTtySupported());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        return $process;
    }

    public function run(array $command, array $env = []): int
    {
        return $this->makeProcess($command, $env)->run();
    }

    public function runInShell(array $command, array $env = []): int
    {
        return $this->makeShell($command, $env)->run();
    }

    public function runAndReturnOutput(array $command, array $env = []): string
    {
        $process = $this->makeProcess($command, $env, false);
        $process->run();

        return $process->getOutput();
    }

    public function runInShellAndReturnOutput(array $command, array $env = []): string
    {
        $process = $this->makeShell($command, $env, false);
        $process->run();

        return $process->getOutput();
    }
}
