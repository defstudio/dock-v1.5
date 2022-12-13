<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Concerns;

use App\Commands\Command;
use App\Docker\Service;
use App\Services\RecipeService;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @mixin Command
 */
trait ForwardsShellCommands
{
    public function __construct()
    {
        parent::__construct();
        $this->ignoreValidationErrors();
    }

    public function handle(RecipeService $cookbook): int
    {
        /** @var class-string<Service> $targetService */
        $targetService = $this->targetService ?? $cookbook->recipe()->getServiceByName($this->signature)::class;

        $arguments = Str::of($this->input->__toString())
            ->when(isset($this->command), fn (Stringable $str) => $str->replaceFirst($this->signature, $this->command)); //@phpstan-ignore-line

        return $this->runInService(
            $targetService,
            $arguments->explode(' ')
                ->map(fn (string $argument) => trim($argument, " '"))
                ->filter()
                ->toArray()
        );
    }
}
