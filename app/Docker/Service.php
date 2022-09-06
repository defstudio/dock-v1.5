<?php

/** @noinspection PhpUnused */
/** @noinspection LaravelFunctionsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Docker;

use App\Exceptions\DockerServiceException;
use App\Recipes\Recipe;
use App\Services\RecipeService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

abstract class Service
{
    protected const HOST_SRC_PATH = './src';

    protected const HOST_SERVICES_PATH = './services';

    protected ServiceDefinition $serviceDefinition;

    /** @var Collection<int, Volume> */
    private Collection $volumes;

    /** @var Collection<string, Network> */
    private Collection $networks;

    protected string $name;

    public function __construct()
    {
        $this->volumes = Collection::empty();
        $this->networks = Collection::empty();

        $this->configure();

        if (!isset($this->serviceDefinition)) {
            throw DockerServiceException::serviceNotConfigured($this->name);
        }
    }

    abstract protected function configure(): void;

    public function name(): string
    {
        return $this->name;
    }

    public function setServiceName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    protected function recipe(): Recipe
    {
        $cookbook = app(RecipeService::class);

        return $cookbook->recipe();
    }

    public function addVolume(string $hostPath, string $containerPath = null): static
    {
        $this->volumes->push(app(Volume::class, ['hostPath' => $hostPath, 'containerPath' => $containerPath]));

        return $this;
    }

    public function mapPort(int $hostPort, int $containerPort = null): static
    {
        $containerPort ??= $hostPort;

        $this->serviceDefinition->push('ports', "$hostPort:$containerPort");
        $this->exposePort($containerPort);

        return $this;
    }

    public function exposePort(int $port): static
    {
        $this->serviceDefinition->push('expose', $port);

        return $this;
    }

    public function dependsOn(string $serviceName): static
    {
        $this->serviceDefinition->push('depends_on', $serviceName);

        return $this;
    }

    public function addNetwork(string $name): static
    {
        $this->networks = $this->networks->put($name, app(Network::class, ['name' => $name]));

        return $this;
    }

    /**
     * @return class-string<Command>[]
     */
    public function commands(): array
    {
        return [];
    }

    protected function isProductionMode(): bool
    {
        return env('ENV') === 'production';
    }

    protected function isDockerHostExposed(): bool
    {
        return (bool) env('EXPOSE_DOCKER_HOST', false);
    }

    public function internalNetworkName(): string
    {
        return $this->recipe()->slug().'_internal_network';
    }

    public function getUserId(): int
    {
        $uid = env('USER_ID', getmyuid());

        if ($uid === false) {
            throw DockerServiceException::unableToDetectCurrentUserId();
        }

        return $uid;
    }

    public function getGroupId(): int
    {
        $uid = env('GROUP_ID', getmyuid());

        if ($uid === false) {
            throw DockerServiceException::unableToDetectCurrentGroupId();
        }

        return $uid;
    }

    public function host(): string
    {
        $host = env('HOST');

        if (empty($host)) {
            throw DockerServiceException::missingHost();
        }

        return $host;
    }

    protected function getWorkingDir(): string
    {
        return (string) $this->serviceDefinition->get('working_dir');
    }

    protected function isBehindReverseProxy(): bool
    {
        return !empty($this->reverseProxyNexwork());
    }

    protected function reverseProxyNexwork(): string
    {
        return (string) env('REVERSE_PROXY_NETWORK', '');
    }
}