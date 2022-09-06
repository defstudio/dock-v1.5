<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;

class Scheduler extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('scheduler');

        $this->target('scheduler');
    }
}
