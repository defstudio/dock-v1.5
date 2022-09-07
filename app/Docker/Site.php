<?php /** @noinspection PhpUnused */

namespace App\Docker;

class Site
{
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

    public function proxy(string $target, int $port): static
    {
        $this->proxyTarget = $target;
        $this->proxyPort = $port;

        return $this;
    }

    public function configuration(): string
    {
        return view('services.nginx.site')->with('site', $this)->render();
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function shouldProxyWebsocket(): bool
    {
        return $this->proxyWebsocket;
    }

    public function getCertificatePath(): string
    {
        return $this->certificatePath;
    }

    public function getCertificateKeyPath(): string
    {
        return $this->certificateKeyPath;
    }

    public function isProxy(): bool
    {
        return isset($this->proxyTarget);
    }

    public function getProxyTarget(): string
    {
        return $this->proxyTarget;
    }

    public function getProxyPort(): int
    {
        return $this->proxyPort;
    }
}
