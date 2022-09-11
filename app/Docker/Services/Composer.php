<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Docker\Services;

use Illuminate\Support\Str;

class Composer extends Php
{
    public const ASSET_START_SCRIPT_PATH = 'build/composer/start_script.sh';

    protected function configure(): void
    {
        parent::configure();

        $this->setServiceName('composer');

        $this->target('composer');

        $this->serviceDefinition->set('restart', 'no');
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
        $this->publishStartScript();
        $this->appendDockerfile();
    }

    private function publishStartScript(): void
    {
        $script = view('services.php.composer.start_script')->with('service', $this);
        $this->assets()->put(self::ASSET_START_SCRIPT_PATH, $script);
    }

    private function appendDockerfile(): void
    {
        $dockerfile = view('services.php.dockerfile.composer')->with('service', $this)->render();

        if (!$this->assets()->exists(self::ASSET_DOCKERFILE_PATH) || Str::of($this->assets()->get(self::ASSET_DOCKERFILE_PATH) ?? '')->contains('COMPOSER')) {
            parent::publishAssets();
        }

        $this->assets()->append(self::ASSET_DOCKERFILE_PATH, $dockerfile);
    }
}
