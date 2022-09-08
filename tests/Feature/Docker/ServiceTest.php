<?php

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Exceptions\DockerServiceException;
use App\Facades\Env;

beforeEach(function () {
    $this->service = new class extends Service
    {
        protected function configure(): void
        {
            $this->setServiceName('bar');
            $this->serviceDefinition = new ServiceDefinition([
                '/foo/bar',
            ]);
        }
    };
});

it('requires to be defined', function () {
    new class extends Service
    {
        protected function configure(): void
        {
            $this->setServiceName('foo');
        }
    };
})->throws(DockerServiceException::class, 'Service foo must be configured in Service::configure() method');

it('can return its name', function () {
    expect($this->service->name())->toBe('bar');
});

it('can override its name', function () {
    $this->service->setServiceName('baz');

    expect($this->service->name())->toBe('baz');
});

it('can add a volume', function () {
    $this->service->addVolume('foo', 'bar');

    expect($this->service)->toHaveVolume('foo', 'bar');
});

it('can map a port', function () {
    $this->service->mapPort(42, 99);

    expect($this->service)
        ->yml('ports')->toBe(['42:99'])
        ->yml('expose')->toBe([99]);
});

it('can set a dependency', function () {
    $this->service->dependsOn('baz');

    expect($this->service)->yml('depends_on')->toContain('baz');
});

it('can add a network', function () {
    $this->service->addNetwork('foo_bar_network');

    expect($this->service)->toHaveNetwork('foo_bar_network');
});

it('can return internal network name', function () {
    Env::fake(['RECIPE' => 'test-recipe']);

    expect($this->service)
        ->internalNetworkName()->toBe('test-recipe_internal_network');
});

it('can return current user id', function () {
    Env::fake(['USER_ID' => 999]);

    expect($this->service)
        ->getUserId()->toBe(999);
});

it('can return current group id', function () {
    Env::fake(['USER_ID' => 999]);

    expect($this->service)
        ->getGroupId()->toBe(999);

    Env::put('GROUP_ID', 42);

    expect($this->service)
        ->getGroupId()->toBe(42);
});

it('can return current HOST', function () {
    Env::fake(['HOST' => 'foo.com']);

    expect($this->service)
        ->host()->toBe('foo.com');
});
