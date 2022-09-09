<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;
use Illuminate\Support\Str;

class Websocket extends Php
{
    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('websocket');

        $this->target('websocket');

        $this->dependsOn('php');

        if ($this->isBehindReverseProxy()) {
            $this->addNetwork($this->reverseProxyNexwork())->external();
        } else {
            $port = (int) $this->env('WEBSOCKET_PORT', 6001);
            $this->mapPort($port, 6001);
        }
    }

    public function publishAssets(): void
    {
        if (!$this->assets()->exists('Dockerfile') || Str::of($this->assets()->get('Dockerfile'))->contains('WEBSOCKET')) {
            parent::publishAssets();
        }

        $this->publishStartScript();
        $this->appendDockerfile();
    }

    private function publishStartScript(): void
    {
        $script = view('services.php.websocket.start_script')->with('service', $this)->render();
        $this->assets()->put('websocket/start_script.sh', $script);
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.websocket')->with('service', $this)->render();
        $this->assets()->append('Dockerfile', $dockerfile);
    }
}
