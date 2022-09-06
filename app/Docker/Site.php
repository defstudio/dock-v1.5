<?php

namespace App\Docker;

class Site
{
    protected string $protocol = 'http';

    protected string $root = '/var/www';

    protected string|null $certificatePath = null;
    protected string|null $certificateKeyPath = null;

    protected bool $proxyWebsocket = false;

    protected string $proxyTarget;
    protected int $proxyPort;

    public function __construct(
        protected string $host,
        protected int $port,
    ) {
    }

    public function root(string $path): static
    {
        $this->root = $path;
        return $this;
    }

    public function certificatePath(string $path = null): static
    {
        $this->certificatePath = $path;
        return $this;
    }

    public function certificateKeyPath(string $path = null): static
    {
        $this->certificateKeyPath = $path;
        return $this;
    }

    public function proxyWebsocket(bool $enabled): static
    {
        $this->proxyWebsocket = $enabled;
        return $this;
    }

    public function protocol(string $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    public function proxy(string $target, int $port): static
    {
        $this->proxyTarget = $target;
        $this->proxyPort = $port;
        return $this;
    }
}
