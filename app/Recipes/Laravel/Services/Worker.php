<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;

class Worker extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('worker');

        $this->target('worker');
    }
}
