<?php

declare(strict_types=1);

namespace App\Facades;

use App\Enums\EnvKey;
use App\Testing\FakeEnv;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool exists()
 * @method static mixed get(EnvKey $key, mixed $default = null)
 * @method static FakeEnv put(EnvKey $key, mixed $value = '')
 * @method static void dump()
 */
class Env extends Facade
{
    public static function fake(array $values = []): FakeEnv
    {
        static::swap($fake = new FakeEnv($values));

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'env_repository';
    }
}
