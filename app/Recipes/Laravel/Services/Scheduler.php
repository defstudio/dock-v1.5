<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;
use Illuminate\Support\Str;

class Scheduler extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('scheduler');

        $this->target('scheduler');
    }

    public function publishAssets(): void
    {
        if (!$this->assets()->exists('Dockerfile') || Str::of($this->assets()->get('Dockerfile') ?? '')->contains('SCHEDULER')) {
            parent::publishAssets();
        }

        $this->publishStartScript();
        $this->appendDockerfile();
    }

    private function publishStartScript(): void
    {
        $script = view('services.php.scheduler.start_script')->with('service', $this);
        $this->assets()->put('scheduler/start_script.sh', $script);
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.scheduler')->with('service', $this)->render();
        $this->assets()->append('Dockerfile', $dockerfile);
    }
}
