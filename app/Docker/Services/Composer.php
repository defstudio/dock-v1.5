<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Docker\Services\Php;

class Composer extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('composer');

        $this->target('composer');

        $this->serviceDefinition->unset('depends_on');
    }

    public function commands(): array
    {
        return [
            Commands\Composer::class,
        ];
    }


}
