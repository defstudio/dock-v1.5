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

use App\Docker\Volume;
use App\Services\RecipeService;
use Symfony\Component\Console\Output\NullOutput;
use function Termwind\renderUsing;

uses(Tests\TestCase::class)
    ->beforeEach(function () {
        app()->bind(RecipeService::class, fn () => new RecipeService(__DIR__.'/Fixtures/Recipes'));
        Storage::fake('cwd');
    })
    ->in('Feature');

expect()->extend('toHaveVolume', function (string $hostPath, string $containerPath) {
    expect($this->value->volumes()->filter(fn (Volume $volume) => $volume->hostPath() === $hostPath && $volume->containerPath() === $containerPath))
        ->count()->toBe(1);

    return $this;
});

expect()->extend('toHaveNetwork', function (string $network) {
    expect($this->value)->getNetworks()->toHaveKey($network);
});

function fakeConsoleRenderer(): NullOutput
{
    $output = new class extends NullOutput
    {
        public array $output = [];

        public function writeln(iterable|string $messages, int $options = self::OUTPUT_NORMAL)
        {
            $messages = \Illuminate\Support\Arr::wrap($messages);

            foreach ($messages as $message) {
                $this->output[] = $message;
            }
        }
    };

    renderUsing($output);

    return $output;
}
