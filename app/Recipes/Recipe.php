<?php

declare(strict_types=1);

namespace App\Recipes;

use App\Docker\Service;
use App\Facades\Terminal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class Recipe
{
    /** @var Collection<string, Service> $services */
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

    abstract protected function buildServices(): void;

    public function build(): void
    {
        $this->buildServices();
    }

    public function commands(): array
    {
        return [];
    }

    /**
     * @template CLASS of App\Docker\Service
     * @param class-string<CLASS>  $serviceClass
     *
     * @return CLASS
     */
    public function addService(string $serviceClass): Service
    {
        /** @var Service $service */
        $service = app($serviceClass);

        $this->services->put($service->serviceName(), $service);

        return $service;
    }
}
