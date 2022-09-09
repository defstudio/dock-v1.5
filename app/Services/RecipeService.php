<?php

/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RecipeException;
use App\Facades\Env;
use App\Recipes\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecipeService
{
    private string $recipesPath;

    /**
     * @var Collection<string, Recipe>
     */
    private Collection $recipes;

    private Recipe $active;

    public function __construct(string|null $recipesPath = null)
    {
        $this->recipesPath = $recipesPath ?? __DIR__.'/../Recipes';
    }

    public function recipe(string $name = null): Recipe
    {
        if (!empty($name)) {
            return $this->findRecipe($name);
        }

        if (!isset($this->active)) {
            if (!Env::exists()) {
                throw RecipeException::missingEnvFile();
            }

            if (empty($recipe = Env::get('RECIPE'))) {
                throw RecipeException::noActiveRecipe();
            }

            $this->activate($recipe);
        }

        return $this->active;
    }

    /**
     * @return Collection<string, Recipe>
     */
    public function availableRecipes(): Collection
    {
        if (!isset($this->recipes)) {
            $this->searchRecipes();
        }

        return $this->recipes;
    }

    public function activate(string $recipe): void
    {
        $this->active = $this->findRecipe($recipe);
    }

    private function findRecipe(string $name): Recipe
    {
        $recipe = $this->availableRecipes()->get($name);

        if ($recipe === null) {
            throw RecipeException::notFound($name, $this->recipesPath);
        }

        return $recipe;
    }

    private function searchRecipes(): void
    {
        $this->recipes = $this->directories()
            ->map(fn (string $directory) => $this->files($directory)
                ->map(function (string $file) use ($directory): string {
                    $content = file_get_contents($this->path("$directory/$file"));

                    if ($content === false) {
                        throw RecipeException::failedToParseFile("$directory/$file");
                    }

                    $namespace = Str::of($content)->match('/namespace(.*);/')->trim();

                    return Str::of($file)->remove('.php')->prepend($namespace, '\\')->toString();
                })
                ->reject(fn (string $class) => !class_exists($class))
                ->first(fn (string $class) => is_subclass_of($class, Recipe::class)))
            ->filter()
            ->mapWithKeys(function (string $recipeClass) {
                /** @var Recipe $recipe */
                $recipe = new $recipeClass();

                return [$recipe->slug() => $recipe];
            });
    }

    /**
     * @return Collection<int, string>
     */
    private function files(string $path = ''): Collection
    {
        /** @phpstan-ignore-next-line */
        return collect(scandir($this->path($path)))
            ->reject(fn (string|false $file) => $file === false || $file === '.' || $file === '..')
            ->filter(fn (string|false $file) => $file !== false && !is_dir($this->path("$path/$file")));
    }

    /**
     * @return Collection<array-key, string>
     */
    private function directories(string $path = ''): Collection
    {
        /** @phpstan-ignore-next-line */
        return collect(scandir($this->path($path)))
            ->reject(fn (string|false $file) => $file === false || $file === '.' || $file === '..')
            ->filter(fn (string|false $file) => $file !== false && is_dir($this->path($file)));
    }

    private function path(string $file = ''): string
    {
        return "$this->recipesPath/$file";
    }
}
