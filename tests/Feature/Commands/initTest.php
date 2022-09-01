<?php

use Illuminate\Support\Facades\Storage;

it("prevents running if .env file exists", function () {
    Storage::fake('cwd')->put('.env', '');

    $this->artisan('init')
        ->expectsOutputToContain('A .env configuration file exist for this project. Run  init --force  to overwrite it with a new configuration')
        ->assertFailed();
});

it('asks for confirmation before overriding an .env file', function () {
    Storage::fake('cwd')->put('.env', '');

    $this->artisan('init --force')
        ->expectsConfirmation("This command will overwrite your .env file. Continue?")
        ->assertFailed();
});

it('makes a backup copy of an existing .env file', function () {
    Storage::fake('cwd')->put('.env', 'old');

    $this->artisan('init --force')
        ->expectsConfirmation("This command will overwrite your .env file. Continue?", 'yes')
        ->assertSuccessful();

    expect(Storage::disk('cwd')->get('.env.backup'))->toBe('old');
});

