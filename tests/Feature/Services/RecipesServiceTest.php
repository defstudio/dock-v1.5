<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Exceptions\RecipeException;
use App\Facades\Env;
use App\Services\RecipeService;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

beforeEach(fn () => $this->service = new RecipeService(__DIR__.'/../../Fixtures/Recipes'));

it('can retrieve recipes', function () {
    expect($this->service->availableRecipes()->keys()->toArray())->toBe(['example-recipe', 'test-recipe']);
});

it('throws an exception if no recipe is active', function () {
    Env::fake(['RECIPE' => '']);

    $this->service->recipe();
})->throws(RecipeException::class, "No recipe defined in .env 'RECIPE' value");

it("throws an exception if recipe doesn't exist", function () {
    Env::fake(['RECIPE' => 'foo']);
    $this->service->recipe();
})->throws(RecipeException::class, 'Recipe [foo] not found');

it('returns active recipe', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    expect($this->service->recipe())->toBeInstanceOf(TestRecipe::class);
});
