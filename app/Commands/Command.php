<?php

namespace App\Commands;

use function Termwind\render;

abstract class Command extends \Illuminate\Console\Command
{

    public function warn($string, $verbosity = null)
    {
        render(view('message', [
            'label' => 'Warning',
            'background' => 'yellow-300',
            'color' => 'black',
            'message' => $string
        ]));
    }

    public function error($string, $verbosity = null)
    {
        render(view('message', [
            'label' => 'Error',
            'background' => 'red-500',
            'color' => 'black',
            'message' => $string
        ]));
    }

}
