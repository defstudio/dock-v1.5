<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Facades\Env;
use App\Facades\Terminal;
use App\Recipes\Recipe;
use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;

class Init extends Command
{
    protected $signature = 'init
                            {--force : force overwriting current configuration}
                            {recipe? : the configuration to be created}';

    protected $description = 'Initialize and configure a new project';

    public function handle(RecipeService $cookbook): int
    {
        if ($this->dotEnvExists()) {
            return self::INVALID;
        }

        $recipeSlug = $this->argument('recipe') ?? Terminal::choose('Select a recipe', $cookbook->availableRecipes()->map(fn (Recipe $recipe) => $recipe->name())->toArray());

        $cookbook->activate($recipeSlug);

        $cookbook->recipe()->setup();

        return self::SUCCESS;
    }

    private function dotEnvExists(): bool
    {
        if (!Env::exists()) {
            return false;
        }

        if (!$this->option('force')) {
            $this->error('A .env configuration file exist for this project. Run <span class="text-yellow px-1">init --force</span> to overwrite it with a new configuration');

            return true;
        }

        if (!$this->components->confirm('This command will overwrite your .env file. Continue?')) {
            return true;
        }

        $this->components->task('Making a backup copy of current .env file', function (): bool {
            Storage::disk('cwd')->delete('.env.backup');
            Storage::disk('cwd')->move('.env', '.env.backup');

            return true;
        });

        return false;
    }
}
