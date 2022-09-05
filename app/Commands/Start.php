<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Commands;

use App\Recipes\Recipe;
use App\Services\RecipeService;
use Illuminate\Support\Facades\Storage;

class Start extends Command
{
    protected $signature = 'start
                            {--build : rebuilds images before starting}
                            {--remove-orphans : remove orphans containers}';

    protected $description = 'Launch docker containers';

    public function handle(RecipeService $cookbook): int
    {
        $cookbook->recipe()->publishDockerComposeFile();

        return self::SUCCESS;
    }
}
