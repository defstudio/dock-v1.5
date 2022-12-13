<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Facades\Env;
use App\Facades\Terminal;
use App\Services\RecipeService;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

it('follows the right steps', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    Storage::fake('cwd');
    Terminal::fake();

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
        ]);

    Terminal::assertRan(['docker-compose', 'up', '-d', '--remove-orphans']);
});
