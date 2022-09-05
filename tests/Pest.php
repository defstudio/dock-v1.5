<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Illuminate\Support\Env;

uses(Tests\TestCase::class)->in('Feature');

function withEnv(array $values, bool $overwriteAll = true): void
{
    if ($overwriteAll) {
        foreach (array_keys($_ENV) as $key) {
            if ($key === 'SHELL_VERBOSITY') {
                continue;
            }

            Env::getRepository()->set($key, '');
        }
    }

    foreach ($values as $key => $value) {
        Env::getRepository()->set($key, $value);
    }
}
