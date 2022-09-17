<?php /** @noinspection ALL */

/** @noinspection PhpCastIsUnnecessaryInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Docker\Service;
use App\Exceptions\DockerServiceException;
use App\Facades\Terminal;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

abstract class Recipe
{
    /** @var Collection<string, Service> */
    protected Collection $services;

    public function __construct()
    {
        $this->services = Collection::empty();
    }

    abstract public function name(): string;

    public function slug(): string
    {
        return Str::slug($this->name());
    }

    public function setup(): Configuration
    {
        $configuration = new Configuration(collect($this->options()));
        $configuration->configure();
        $configuration->writeEnv();

        Terminal::successBanner('The configuration has been stored in .env file');

        return $configuration;
    }

    /**
     * @return ConfigurationSection[]
     */
    abstract public function options(): array;

    /**
     * @return class-string<Command>[]
     */
    abstract public function commands(): array;

    abstract protected function buildServices(): void;

    public function build(): void
    {
        $this->buildServices();
    }

    /**
     * @template CLASS
     *
     * @param  class-string<CLASS>  $serviceClass
     * @return CLASS
     */
    public function addService(string $serviceClass)
    {
        /** @var CLASS $service */
        $service = app($serviceClass);

        $this->services->put($serviceClass, $service);

        return $service;
    }

    /**
     * @template CLASS
     *
     * @param class-string<CLASS> $serviceClass
     *
     * @return CLASS&Service
     */
    public function getService(string $serviceClass)
    {
        $service = $this->services->get($serviceClass);

        if (empty($service)) {
            throw DockerServiceException::serviceNotFound($serviceClass);
        }

        return $service;
    }

    /**
     * @return Collection<string, Service>
     */
    public function services(): Collection
    {
        return $this->services;
    }

    public function publishDockerCompose(): bool
    {
        $yml = Yaml::dump([
            'version' => '3.5',
            'services' => $this->servicesYml(),
            'networks' => $this->networksYml(),
        ], 4);

        return (bool) Storage::disk('cwd')->put('docker-compose.yml', $yml);
    }

    private function servicesYml(): array
    {
        return $this->services->map(fn (Service $service) => $service->yml())->toArray();
    }

    public function publishAssets(): bool
    {
        $this->services->each(fn (Service $service) => $service->publishAssets());

        return true;
    }

    private function networksYml(): array
    {
        return $this->services->flatMap(fn (Service $service) => $service->getNetworks())->toArray();
    }

    public function getDatabaseService()
    {

    }
}
