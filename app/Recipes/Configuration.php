<?php /** @noinspection PhpUndefinedFieldInspection */

namespace App\Recipes;

use Illuminate\Support\Collection;
use function Termwind\render;

class Configuration
{
    /** @var array<string, string> */
    private array $extraOptions = [];

    public function __construct(private Collection $sections)
    {
    }

    public function setup(): void
    {
        $this->sections->each(function (ConfigurationSection $section) {
            render("<div class='m-1 p-1 min-w-50 bg-green text-black text-center'>{$section->name()}</div>");
            $section->options()->each(fn (ConfigurationOption $option) => $option->setup($this));
        });
    }

    private function find(string $key): ConfigurationOption|null
    {
        return $this->sections->flatMap(fn (ConfigurationSection $section) => $section->options())->first(fn (ConfigurationOption $option) => $option->key() === $key);
    }

    public function get(string $key): string
    {
        return $this->find($key)?->value() ?? '';
    }

    public function set(string $key, string $value): void
    {
        $option = $this->find($key);

        if ($option) {
            invade($option)->value = $value;
            return;
        }

        $this->extraOptions[$key] = $value;
    }
}
