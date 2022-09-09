<?php

/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Facades\Terminal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Configuration
{
    /** @var array<string, string|int|bool> */
    private array $extraOptions = [];

    /**
     * @param  Collection<int, ConfigurationSection>  $sections
     */
    public function __construct(private readonly Collection $sections)
    {
    }

    public function configure(): void
    {
        $this->sections->each(function (ConfigurationSection $section) {
            if (!$section->options()->some(fn (ConfigurationOption $option) => $option->isActive($this) && !$option->hasASettedValue())) {
                return;
            }

            Terminal::render("<div class='m-1 p-1 min-w-50 bg-gray text-black text-center'>{$section->name()}</div>");
            $section->options()->each(fn (ConfigurationOption $option) => $option->configure($this));
        });
    }

    private function find(string $key): ConfigurationOption|null
    {
        return $this->sections->flatMap(fn (ConfigurationSection $section) => $section->options())->first(fn (ConfigurationOption $option) => $option->key() === $key);
    }

    public function dump(): void
    {
        $this->sections->flatMap(fn (ConfigurationSection $section) => $section->options())
            ->mapWithKeys(fn (ConfigurationOption $option) => [$option->key() => $option->value()])
            ->dump();
    }

    public function get(string $key, string|int|bool $default = ''): string|int|bool
    {
        return $this->find($key)?->value() ?? $this->extraOptions[$key] ?? $default;
    }

    public function set(string $key, string|int|bool $value): void
    {
        $option = $this->find($key);

        if ($option) {
            invade($option)->value = $value;

            return;
        }

        $this->extraOptions[$key] = $value;
    }

    /**
     * @return array<string, string|int|bool>
     */
    public function extraOptions(): array
    {
        return $this->extraOptions;
    }

    public function writeEnv(): void
    {
        $env = Str::of('');

        $maxSectionNameLength = $this->sections->max(fn (ConfigurationSection $section) => strlen($section->name()));

        if (!empty($this->extraOptions)) {
            $this->sections->push(ConfigurationSection::make('Extra', [
                ...collect($this->extraOptions)->map(fn (string|int|bool $value, string $key) => ConfigurationOption::make($key, $value)),
            ]));
        }

        $this->sections->each(function (ConfigurationSection $section) use ($maxSectionNameLength, &$env) {
            $sectionNameLength = strlen($section->name());
            $spaces = $maxSectionNameLength - $sectionNameLength + 2;
            $spacesBefore = (int) floor($spaces / 2);
            $spacesAfter = (int) ceil($spaces / 2);

            $options = $section->options()->map(function (ConfigurationOption $option) {
                if ($option->value() === '' && !$option->shouldExportIfEmpty()) {
                    return null;
                }

                if (!$option->shouldShowInEnv()) {
                    return null;
                }

                return Str::of($option->key())
                    ->append('=')
                    ->append(match ($option->value()) {
                        true => 1,
                        false => 0,
                        default => $option->value(),
                    })
                    ->append("\n")
                    ->when($option->getDescription(), fn (Stringable $str) => $str->prepend('# ', $option->getDescription(), "\n"))->toString();
            })->filter();

            if ($options->isEmpty()) {
                return;
            }

            $env = $env->append("\n\n")
                ->append(str_repeat('#', $maxSectionNameLength + 4), "\n")
                ->append('#', str_repeat(' ', $spacesBefore))
                ->append($section->name())
                ->append(str_repeat(' ', $spacesAfter), '#', "\n")
                ->append(str_repeat('#', $maxSectionNameLength + 4), "\n")
                ->append("\n")
                ->append($options->join("\n"));
        });

        Storage::disk('cwd')->put('.env', $env->trim()->append("\n")->toString());
    }

    public function toArray(): array
    {
        return $this->sections
            ->flatMap(fn (ConfigurationSection $section) => $section->options())
            ->mapWithKeys(fn (ConfigurationOption $option) => [$option->key() => $option->value()])
            ->toArray();
    }
}
