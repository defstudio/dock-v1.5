<?php

use App\Facades\Env;
use App\Facades\Terminal;
use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;

it('prevents running if .env file exists', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    Terminal::fake();

    $this->artisan('init')
        ->assertFailed();

    Terminal::assertSent('Error A .env configuration file exist for this project. Run  init --force  to overwrite it with a new configuration');
});

it('asks for confirmation before overriding an .env file', function () {
    Storage::fake('cwd')->put('.env', '');

    $this->artisan('init --force')
        ->expectsConfirmation('This command will overwrite your .env file. Continue?')
        ->assertFailed();
});

it('makes a backup copy of an existing .env file', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    Terminal::fake(['Select a recipe' => 'test-recipe', 'SUCCESS!', 'The configuration has been stored in .env file']);

    Storage::fake('cwd')->put('.env', 'old');
    app()->singleton(RecipeService::class, fn () => new RecipeService(__DIR__.'/../../Fixtures/Recipes'));

    $this->artisan('init --force')
        ->expectsConfirmation('This command will overwrite your .env file. Continue?', 'yes')
        ->assertSuccessful();

    expect(Storage::disk('cwd')->get('.env.backup'))->toBe('old');
});
