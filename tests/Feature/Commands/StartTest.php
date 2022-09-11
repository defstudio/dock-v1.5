<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Facades\Env;
use App\Services\RecipeService;
use Symfony\Component\Process\Process;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

it('follows the right steps', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    Storage::fake('cwd');

    $process = new class extends Process
    {
        public bool $run = false;

        public array $command;

        public function __construct(array $command = [], array $env = [])
        {
            parent::__construct($command);
        }

        public function run(callable $callback = null, array $env = []): int
        {
            $this->run = true;

            return 0;
        }
    };

    app()->bind(Process::class, fn () => $process);

    $cookbook = app(RecipeService::class);

    invade($cookbook)->active = new class extends TestRecipe
    {
        public array $steps = [];

        public function publishDockerCompose(): bool
        {
            $this->steps[] = 'publish docker-compose';

            return true;
        }

        public function publishAssets(): bool
        {
            $this->steps[] = 'publish assets';

            return true;
        }
    };

    app()->bind(RecipeService::class, fn () => $cookbook);

    $this->artisan('start')->assertSuccessful();

    expect(Storage::disk('cwd')->exists('src'))
        ->and($cookbook->recipe()->steps)->toBe([
            'publish docker-compose',
            'publish assets',
        ])->and($process->run)->toBeTrue();
});
