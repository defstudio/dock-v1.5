<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Commands;

use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Process\Process;
use function Termwind\render;
use function Termwind\terminal;

abstract class Command extends \Illuminate\Console\Command
{
    private int $writeCount = 0;

    public function runInTerminal(array $command, array $env = null, bool $withTty = false): int
    {
        $process = new Process(command: $command, env: $env);

        if ($this->input instanceof StreamableInputInterface && $stream = $this->input->getStream()) {
            $process->setInput($stream);
        }

        $process->setTty($withTty && Process::isTtySupported());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        return $process->run(function ($type, $message) {
            if ($this->writeCount() === 0) {
                $this->output->newLine();
            }

            $firstWrite = true;
            while (strlen($message) > 0) {
                $piece = substr($message, 0, terminal()->width() - 6);
                $message = substr($message, strlen($piece));

                $piece = str_replace("\n", "      \n", $piece);
                if ($firstWrite) {
                    $this->write("    <fg=green>> </>$piece");
                    $firstWrite = false;
                } else {
                    $this->write("      $piece");
                }
            }
        });
    }

    public function runInService(string $service, array $command, array $env = null, bool $withTty = false): int
    {
        $containerCommand = ['docker-compose', 'run', '--service-ports', '--rm'];

        if ($withTty) {
            $containerCommand[] = '-T';
        }

        $containerCommand[] = $service;

        return $this->runInTerminal([
            ...$containerCommand,
            ...$command,
        ], $env, $withTty);
    }

    public function warn($string, $verbosity = null)
    {
        render((string) view('message', [
            'label' => 'Warning',
            'background' => 'yellow-300',
            'color' => 'black',
            'message' => $string,
        ]));
    }

    public function error($string, $verbosity = null)
    {
        render((string) view('message', [
            'label' => 'Error',
            'background' => 'red-500',
            'color' => 'black',
            'message' => $string,
        ]));
    }

    /**
     * @param array<string, callable(): bool> $tasks
     */
    public function tasks(array $tasks): bool
    {
        foreach ($tasks as $title => $task) {
            $this->output->write("  <bg=gray>$title</>");

            $titleWidth = mb_strlen($title);
            $startTime = microtime(true);

            $this->resetWriteCount();
            $result = $task();

            $runTime = number_format((microtime(true) - $startTime) * 1000).'ms';
            $runTimeWidth = mb_strlen($runTime);

            $width = min(terminal()->width(), 150);
            $dots = max($width - $titleWidth - $runTimeWidth - 10, 0);


            if ($this->writeCount() > 0) {
                $this->write("    ".str_repeat('<fg=gray>.</>', $titleWidth));
            }
            $this->output->write(str_repeat('<fg=gray>.</>', $dots));
            $this->output->write("<fg=gray>$runTime</>");

            $this->output->writeln($result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>');

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public function write(string $message, bool $newLine = false): void
    {
        $this->writeCount++;
        $this->output->write($message, $newLine);
    }

    public function writeLn(string $message): void
    {
        $this->writeCount++;
        $this->output->writeln($message);
    }

    protected function resetWriteCount(): void
    {
        $this->writeCount = 0;
    }

    protected function writeCount(): int
    {
        return $this->writeCount;
    }
}
