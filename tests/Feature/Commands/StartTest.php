<?php /** @noinspection PhpUnhandledExceptionInspection */

use App\Facades\Env;
use App\Services\RecipeService;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

it('follows the right steps', function () {
    Env::fake(['RECIPE' => 'test-recipe']);

    $cookbook = app(RecipeService::class);

    invade($cookbook)->active = new class extends TestRecipe
    {
        public array $steps = [];

        public function publishDockerCompose(): bool
        {
            $this->steps[] = 'publish docker-compose';

            return true;
        }
    };

    app()->bind(RecipeService::class, fn() => $cookbook);

    $this->artisan('start')->assertSuccessful();

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    expect($cookbook->recipe()->steps)->toBe([
        'publish docker-compose',
    ]);
});
