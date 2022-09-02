<?php

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
}
