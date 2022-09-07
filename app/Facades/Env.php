<?php

declare(strict_types=1);

namespace App\Facades;

use App\Testing\FakeEnv;
use App\Testing\FakeTerminal;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool exists()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static FakeEnv put(string $key, mixed $value)
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
