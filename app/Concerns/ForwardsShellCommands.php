<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Concerns;


use App\Commands\Command;
use App\Services\RecipeService;
use Illuminate\Support\Str;

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
        $targetService = $this->targetService ?? $cookbook->recipe()->getServiceByName($this->signature)::class;

        $arguments = $this->input->__toString();

        if(isset($this->command)){
            $arguments = Str::of($arguments)->replaceFirst($this->signature, $this->command);
        }


        return $this->runInService(
            $targetService,
            Str::of($arguments)->explode(' ')
                ->map(fn (string $argument) => trim($argument, " '"))
                ->filter()
                ->toArray()
        );
    }
}
