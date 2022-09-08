<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Docker\Services;

use Illuminate\Support\Str;

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

    public function publishAssets(): void
    {
        $this->appendDockerfile();
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.composer')->with('service', $this)->render();

        if (!$this->assets()->exists('Dockerfile') || Str::of($this->assets()->get('Dockerfile'))->contains('COMPOSER')) {
            parent::publishAssets();
        }

        $this->assets()->append('Dockerfile', $dockerfile);
    }
}
