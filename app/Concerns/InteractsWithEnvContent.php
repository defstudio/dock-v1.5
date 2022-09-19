<?php

/** @noinspection PhpUnused */

namespace App\Concerns;

use Illuminate\Support\Str;

trait InteractsWithEnvContent
{
    protected function commentEnvValue(string &$envContent, string $key): void
    {
        $envContent = preg_replace("/^$key=/m", "#$key=", $envContent);
    }

    protected function uncommentEnvValue(string &$envContent, string $key): void
    {
        $envContent = preg_replace("/^#$key=/m", "$key=", $envContent);
    }

    protected function setEnvValue(string &$envContent, string $key, string|int $value): void
    {
        $this->uncommentEnvValue($envContent, $key);
        if (Str::contains($envContent, $key)) {
            $envContent = preg_replace("/^$key=.*\$/m", "$key=$value", $envContent);
        } else {
            $envContent .= "\n$key=$value";
        }
    }

    protected function getEnvValue(string $envContent, string $key): string|int
    {
        $matches = [];
        if (preg_match("/^$key=(.*)\$/m", $envContent, $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }
}
