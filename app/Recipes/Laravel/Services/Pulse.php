<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Recipes\Laravel\Services;

use App\Docker\Services\Php;
use Illuminate\Support\Str;

class Pulse extends Php
{
    public const ASSET_START_SCRIPT_PATH = 'build/pulse/start_script.sh';

    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('pulse');

        $this->target('pulse');
    }

    public function publishAssets(): void
    {
        if (!$this->assets()->exists(self::ASSET_DOCKERFILE_PATH) || Str::of($this->assets()->get(self::ASSET_DOCKERFILE_PATH) ?? '')->contains('PULSE')) {
            parent::publishAssets();
        }

        $this->publishStartScript();
        $this->appendDockerfile();
    }

    private function publishStartScript(): void
    {
        $script = view('services.php.pulse.start_script')->with('service', $this);
        $this->assets()->put(self::ASSET_START_SCRIPT_PATH, $script);
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.worker')->with('service', $this)->render();
        $this->assets()->append(self::ASSET_DOCKERFILE_PATH, $dockerfile);
    }

    public function commands(): array
    {
        return [];
    }
}
