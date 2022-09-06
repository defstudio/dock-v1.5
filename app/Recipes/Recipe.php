<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Recipes;

use App\Docker\Service;
use App\Exceptions\DockerServiceException;
use App\Facades\Terminal;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class Recipe
{
    /** @var Collection<string, Service> */
    public Collection $services;

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
        $configuration->writeEnv($this->slug());

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

        $this->services->put($service->name(), $service);

        return $service;
    }

    public function getService(string $name): Service
    {
        $service = $this->services->get($name);

        if (empty($service)) {
            throw DockerServiceException::serviceNotFound($name);
        }

        return $service;
    }

    public function publishDockerCompose(): bool
    {
        return true;
    }
}