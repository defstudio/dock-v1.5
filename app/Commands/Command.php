<?php

declare(strict_types=1);

namespace App\Commands;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function Termwind\render;

abstract class Command extends \Illuminate\Console\Command
{
    public function runInTerminal(array $command, array $environment_variables = null): int
    {
        $process = new Process(command: $command, env: $environment_variables);

        if ($this->input instanceof StreamableInputInterface && $stream = $this->input->getStream()) {
            $process->setInput($stream);
        }

        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        return $process->run(function($type, $message){
            $this->output->write($message);
        });
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
}
