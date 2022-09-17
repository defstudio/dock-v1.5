<?php /** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Commands;

use App\Docker\Service;
use App\Facades\Terminal;
use App\Services\RecipeService;
use function Termwind\terminal;

abstract class Command extends \Illuminate\Console\Command
{
    private int $writeCount = 0;

    public function runInTerminal(array $command, array $env = []): int
    {
        return Terminal::run($command, $env);
    }

    /**
     * @param class-string<Service> $serviceClass
     */
    public function runInService(string $serviceClass, array $command, array $env = [], bool $withTty = true): int
    {
        $service = app(RecipeService::class)->recipe()->getService($serviceClass);

        if ($service->isRunning()) {
            $dockerComposeCommand = ['docker-compose', 'exec'];
        } else {
            $dockerComposeCommand = ['docker-compose', 'run', '--service-ports', '--rm'];

            if (!$withTty) {
                $dockerComposeCommand[] = '-T';
            }
        }

        $command = [
            ...$dockerComposeCommand,
            $service->name(),
            ...$command,
        ];

        return $this->runInTerminal($command, $env);
    }

    public function warn($string, $verbosity = null)
    {
        Terminal::render((string) view('message', [
            'label' => 'Warning',
            'background' => 'yellow-300',
            'color' => 'black',
            'message' => $string,
        ]));
    }

    public function error($string, $verbosity = null)
    {
        Terminal::render((string) view('message', [
            'label' => 'Error',
            'background' => 'red-500',
            'color' => 'black',
            'message' => $string,
        ]));
    }

    public function step(string $string, string $color = 'green'): void
    {
        Terminal::render((string) view('step', [
            'color' => $color,
            'message' => $string,
        ]));
    }

    /**
     * @param  array<string, callable(): bool>  $tasks
     */
    public function tasks(array $tasks): bool
    {
        foreach ($tasks as $title => $task) {
            if(is_numeric($title)){
                return $task();
            }

            $this->output->newLine();
            $this->output->writeln("  <bg=gray>$title</>");

            $startTime = microtime(true);

            $this->resetWriteCount();
            $result = $task();

            $runTime = number_format((microtime(true) - $startTime) * 1000).'ms';
            $runTimeWidth = mb_strlen($runTime);

            $width = min(terminal()->width(), 150);
            $dots = max($width - $runTimeWidth - 10, 0);

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
