<?php

namespace App\Commands;

use Illuminate\Support\Facades\Storage;
use function Termwind\ask;

class Init extends Command
{
    protected $signature = 'init
                            {--force : force overwriting current configuration}
                            {recipe? : the configuration to be created}';

    protected $description = 'Initialize and configure a new project';


    public function handle(): int
    {
        if ($this->dotEnvExists()) {
            return self::INVALID;
        }

        $this->components->twoColumnDetail('aaaa', 'bbbb');

        return self::SUCCESS;
    }

    private function dotEnvExists(): bool
    {
        if (!Storage::disk('cwd')->exists('.env')) {
            return false;
        }

        if (!$this->option('force')) {
            $this->error('A .env configuration file exist for this project. Run <span class="bg-blue-500 text-black px-1">init --force</span> to overwrite it with a new configuration');
            return true;
        }

        if (!$this->components->confirm("This command will overwrite your .env file. Continue?")) {
            return true;
        }

        $this->components->task("Making a backup copy of current .env file", function (): bool {
            Storage::disk('cwd')->delete('.env.backup');
            Storage::disk('cwd')->move('.env', '.env.backup');

            return true;
        });

        return false;
    }
}
