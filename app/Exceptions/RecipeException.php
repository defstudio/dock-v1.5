<?php

declare(strict_types=1);

namespace App\Exceptions;

class RecipeException extends \Exception
{
    public static function noActiveRecipe(): self
    {
        return new self("No recipe defined in .env 'RECIPE' value");
    }

    public static function notFound(string $recipe): self
    {
        return new self("Recipe [$recipe] not found");
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
