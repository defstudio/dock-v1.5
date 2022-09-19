<?php

/** @noinspection LaravelFunctionsInspection */

namespace App\Repositories;

use App\Enums\EnvKey;
use Illuminate\Support\Facades\Storage;

class Env
{
    public function exists(): bool
    {
        return Storage::disk('cwd')->exists('.env');
    }

    public function get(EnvKey $key, mixed $default = null): mixed
    {
        return env($key->value, $default);
    }
}
