<?php

/** @noinspection LaravelFunctionsInspection */

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class Env
{
    public function exists(): bool
    {
        return Storage::disk('cwd')->exists('.env');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return env($key, $default);
    }
}
