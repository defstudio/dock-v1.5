<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Exceptions\RecipeException;
use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

beforeEach(fn () => $this->service = new RecipeService(__DIR__.'/../../Fixtures/Recipes'));

it('can retrieve recipes', function () {
    expect($this->service->availableRecipes()->keys()->toArray())->toBe(['example-recipe', 'test-recipe']);
});

it('throws an exception if no recipe is active', function () {
    Storage::fake('cwd')->put('.env', 'RECIPE=');
    withEnv(['RECIPE' => '']);

    $this->service->recipe();
})->throws(RecipeException::class, "No recipe defined in .env 'RECIPE' value");

it("throws an exception if recipe doesn't exist", function () {
    Storage::fake('cwd')->put('.env', 'RECIPE=foo');

    withEnv(['RECIPE' => 'foo']);
    $this->service->recipe();
})->throws(RecipeException::class, 'Recipe [foo] not found');

it('returns active recipe', function () {
    Storage::fake('cwd')->put('.env', 'RECIPE=test-recipe');
    withEnv(['RECIPE' => 'test-recipe']);

    expect($this->service->recipe())->toBeInstanceOf(TestRecipe::class);
});
