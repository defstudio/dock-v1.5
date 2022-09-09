<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class RecipeException extends \Exception
{
    public static function noActiveRecipe(): self
    {
        return new self("No recipe defined in .env 'RECIPE' value");
    }

    public static function notFound(string $recipe, string $path): self
    {
        $message = Str::of("Recipe [$recipe] not found in $path.")
            ->when(Str::of($path)->contains('Fixtures/Recipes'), fn (Stringable $str) => $str
                ->append(" ")
                ->append("You are using test Recipes path, you can restore the default one adding [restoreDefaultRecipes()] to your test code."))
            ->toString();

        return new self($message);
    }

    public static function missingEnvFile(): self
    {
        $appName = config('app.name');

        return new self("No .env file found. Run '$appName init' to configure a new recipe");
    }

    public static function failedToParseFile(string $file): self
    {
        return new self("Failed to parse [$file] content");
    }
}
