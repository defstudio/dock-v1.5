<?php

/** @noinspection PhpUnhandledExceptionInspection */

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
use App\Facades\Env;
use App\Facades\Terminal;
use App\Services\RecipeService;
use Symfony\Component\Console\Output\NullOutput;
use function Termwind\renderUsing;

uses(Tests\TestCase::class)
    ->beforeEach(function () {
        app()->bind(RecipeService::class, fn () => new RecipeService(__DIR__.'/Fixtures/Recipes'));
        Storage::fake('cwd');
        Terminal::fake();
    })
    ->group('builds')
    ->in('Builds');

uses(Tests\TestCase::class)
    ->beforeEach(function () {
        app()->bind(RecipeService::class, fn () => new RecipeService(__DIR__.'/Fixtures/Recipes'));
        Storage::fake('cwd');
        Terminal::fake();
    })
    ->in('Feature');

expect()->extend('toHaveVolume', function (string $hostPath, string $containerPath) {
    expect($this->value->volumes()->filter(fn (Volume $volume) => $volume->hostPath() === $hostPath && $volume->containerPath() === $containerPath))
        ->count()->toBe(1);

    return $this;
});

expect()->extend('toHaveNetwork', function (string $network) {
    expect($this->value)->getNetworks()->toHaveKey($network);

    return $this;
});

function fakeConsoleRenderer(): NullOutput
{
    $output = new class extends NullOutput
    {
        public array $output = [];

        public function write(iterable|string $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
        {
            $messages = \Illuminate\Support\Arr::wrap($messages);

            foreach ($messages as $message) {
                if (!str_ends_with($message, "\n") && $newline) {
                    $message = "$message\n";
                }
                $this->output[] = $message;
            }
        }

        public function writeln(iterable|string $messages, int $options = self::OUTPUT_NORMAL)
        {
            $messages = \Illuminate\Support\Arr::wrap($messages);

            foreach ($messages as $message) {
                if (!str_ends_with($message, "\n")) {
                    $message = "$message\n";
                }
                $this->output[] = $message;
            }
        }
    };

    renderUsing($output);

    return $output;
}

function restoreDefaultRecipes(): void
{
    $service = new RecipeService();

    if (!empty(Env::get('RECIPE'))) {
        $service->recipe()->build();
    }

    app()->bind(RecipeService::class, fn () => $service);
}
