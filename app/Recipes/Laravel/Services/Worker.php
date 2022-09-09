<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;
use Illuminate\Support\Str;

class Worker extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('worker');

        $this->target('worker');
    }

    public function publishAssets(): void
    {
        if (!$this->assets()->exists('Dockerfile') || Str::of($this->assets()->get('Dockerfile') ?? '')->contains('WORKER')) {
            parent::publishAssets();
        }

        $this->publishStartScript();
        $this->appendDockerfile();
    }

    private function publishStartScript(): void
    {
        $script = view('services.php.worker.start_script')->with('service', $this);
        $this->assets()->put('worker/start_script.sh', $script);
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.worker')->with('service', $this)->render();
        $this->assets()->append('Dockerfile', $dockerfile);
    }
}
