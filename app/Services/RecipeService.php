<?php /** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection LaravelFunctionsInspection */

namespace App\Services;

use App\Exceptions\RecipeException;
use App\Recipes\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecipeService
{
    /**
     * @var Collection<string, Recipe>
     */
    private Collection $recipes;

    private Recipe $active;

    public function __construct(private string|null $recipesPath = null)
    {
        $this->recipesPath ??= __DIR__ . "/../Recipes";
    }

    public function recipe(): Recipe
    {
        if(!isset($this->active)){
            if (empty($recipe = env('RECIPE'))) {
                throw RecipeException::noActiveRecipe();
            }

            $this->activate($recipe);
        }

        return $this->active;
    }

    public function availableRecipes(): Collection
    {
        if (!isset($this->recipes)) {
            $this->searchRecipes();
        }

        return $this->recipes;
    }

    public function activate(string $recipe): void
    {
        if (!$this->availableRecipes()->has($recipe)) {
            throw RecipeException::notFound($recipe);
        }

        $this->active = $this->availableRecipes()->get($recipe);
    }

    private function searchRecipes(): void
    {
        $this->recipes = $this->directories()
            ->map(fn (string $directory) => $this->files($directory)
                ->map(function (string $file) use ($directory) {
                   $content = file_get_contents($this->path("$directory/$file"));
                   $namespace = Str::of($content)->match("/namespace(.*);/")->trim();
                    return Str::of($file)->remove('.php')->prepend($namespace, '\\');
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

    private function files(string $path = ''): Collection
    {
        return collect(scandir($this->path($path)))
            ->reject(fn (string $file) => $file === '.' || $file === '..')
            ->filter(fn (string $file) => !is_dir($this->path($file)));
    }

    private function directories(string $path = ''): Collection
    {
        return collect(scandir($this->path($path)))
            ->reject(fn (string $file) => $file === '.' || $file === '..')
            ->filter(fn (string $file) => is_dir($this->path($file)));
    }

    private function path(string $file = ''): string
    {
        return "$this->recipesPath/$file";
    }
}
