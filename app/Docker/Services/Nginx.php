<?php

/** @noinspection PhpUnused */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Docker\Services;

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Docker\Services\Commands\NginxRestart;
use App\Docker\Site;
use App\Exceptions\DockerServiceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Nginx extends Service
{
    protected const LETSENCRYPT_FOLDER = '/etc/letsencrypt';

    protected const ASSET_NGINX_CONF_PATH = 'nginx.conf';

    protected const ASSET_UPSTREAM_CONF_PATH = 'conf.d/upstream.conf';

    protected const ASSET_SITES_AVAILABLE_DIRECTORY = 'sites-available';

    protected Php $phpService;

    /** @var Collection<string, Site> */
    protected Collection $sites;

    protected bool $hostNotFoundPage = false;

    public function __construct()
    {
        $this->sites = Collection::make();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setServiceName('nginx');

        $this->serviceDefinition = new ServiceDefinition([
            'restart' => 'unless-stopped',
            'working_dir' => '/var/www',
            'build' => [
                'context' => "{$this->assetsFolder()}/build",
            ],
        ]);

        if ($this->isDockerHostExposed()) {
            $this->serviceDefinition->push('extra_hosts', 'host.docker.internal:host-gateway');
        }

        $this->addVolume(self::HOST_SRC_PATH, $this->getWorkingDir());
        $this->addVolume(self::HOST_SERVICES_PATH."/$this->name/".self::ASSET_NGINX_CONF_PATH, '/etc/nginx/nginx.conf');
        $this->addVolume(self::HOST_SERVICES_PATH."/$this->name/".self::ASSET_SITES_AVAILABLE_DIRECTORY, '/etc/nginx/sites-available');

        $this->setupSite();

        $this->addNetwork($this->internalNetworkName());

        if ($this->isBehindReverseProxy()) {
            $this->addNetwork($this->reverseProxyNexwork())->external();
        }
    }

    public function phpService(Php $php): static
    {
        $this->phpService = $php;
        $this->serviceDefinition->push('depends_on', $php->name());

        $this->addVolume(self::HOST_SERVICES_PATH."/$this->name/".self::ASSET_UPSTREAM_CONF_PATH, '/etc/nginx/conf.d/upstream.conf');

        return $this;
    }

    protected function setExternalCertificate(): void
    {
        $externalCertificatesFolder = $this->env('NGINX_EXTERNAL_CERTIFICATE_FOLDER');

        if (empty($externalCertificatesFolder)) {
            return;
        }

        $exists = Str::of($externalCertificatesFolder)->startsWith(DIRECTORY_SEPARATOR)
            ? File::exists($externalCertificatesFolder)
            : Storage::disk('cwd')->exists($externalCertificatesFolder);

        if (!$exists) {
            throw DockerServiceException::invalidPath($externalCertificatesFolder);
        }

        $this->addVolume($externalCertificatesFolder, self::LETSENCRYPT_FOLDER);
    }

    protected function setupSite(): void
    {
        $port = (int) $this->env('NGINX_PORT', 80);

        if ($port === 443) {
            $this->setupSslSite();

            return;
        }

        $this->addSite($this->host(), $port)
            ->root($this->getWorkingDir())
            ->proxyWebsocket((bool) $this->env('WEBSOCKET_ENABLED'));
    }

    protected function setupSslSite(): void
    {
        $this->setExternalCertificate();

        if ($this->env('NGINX_EXTERNAL_CERTIFICATE_FOLDER')) {
            $certificateHostname = (string) $this->env('NGINX_EXTERNAL_CERTIFICATE_HOSTNAME', $this->host());

            $sslCertificate = self::LETSENCRYPT_FOLDER."/live/$certificateHostname/fullchain.pem";
            $sslCertificateKey = self::LETSENCRYPT_FOLDER."/live/$certificateHostname/privkey.pem";
        }

        $this->addSite($this->host(), 443)
            ->root($this->getWorkingDir())
            ->certificatePath($sslCertificate ?? self::LETSENCRYPT_FOLDER."/live/{$this->host()}/fullchain.pem")
            ->certificateKeyPath($sslCertificateKey ?? self::LETSENCRYPT_FOLDER."/live/{$this->host()}/privkey.pem")
            ->proxyWebsocket((bool) $this->env('WEBSOCKET_ENABLED'));
    }

    public function addSite(string $host, int $port): Site
    {
        $site = new Site($host, $port);
        $this->sites->put($site->getHost(), $site);

        if ($this->isBehindReverseProxy()) {
            $this->exposePort($port);
        } else {
            $this->mapPort($port);
        }

        return $site;
    }

    public function enableHostNotFoundPage(bool $enable = true): static
    {
        $this->hostNotFoundPage = $enable;

        return $this;
    }

    public function commands(): array
    {
        return [
            NginxRestart::class,
        ];
    }

    /**
     * @return Collection<string, Site>
     */
    public function sites(): Collection
    {
        return $this->sites;
    }

    public function getSite(string $host): Site|null
    {
        return $this->sites->get($host);
    }

    public function hostNotFoundPageEnabled(): bool
    {
        return $this->hostNotFoundPage;
    }

    public function getPhpService(): Php
    {
        return $this->phpService;
    }

    public function publishAssets(): void
    {
        $this->publishDockerfile();
        $this->publishNginxConfigFile();
        $this->publishUpstreamConfig();
        $this->publishSitesAvailableDirectory();
        $this->publishSites();
        $this->publishHostNotFoundSite();
    }

    private function publishDockerfile(): void
    {
        $this->assets()->put(
            self::ASSET_DOCKERFILE_PATH,
            view('services.nginx.dockerfile.main')->with('service', $this)->render()
        );
    }

    private function publishNginxConfigFile(): void
    {
        $this->assets()->put(
            self::ASSET_NGINX_CONF_PATH,
            view('services.nginx.nginx_conf')->with('service', $this)->render()
        );
    }

    private function publishUpstreamConfig(): void
    {
        if (empty($this->phpService)) {
            return;
        }

        $this->assets()->put(
            self::ASSET_UPSTREAM_CONF_PATH,
            view('services.nginx.upstream_conf')->with('service', $this)->render()
        );
    }

    private function publishSitesAvailableDirectory(): void
    {
        if ($this->assets()->exists(self::ASSET_SITES_AVAILABLE_DIRECTORY)) {
            $this->assets()->deleteDirectory(self::ASSET_SITES_AVAILABLE_DIRECTORY);
        }

        $this->assets()->makeDirectory(self::ASSET_SITES_AVAILABLE_DIRECTORY);
    }

    private function publishSites(): void
    {
        $this->sites->each(fn (Site $site) => $this->assets()->put(
            Str::of(self::ASSET_SITES_AVAILABLE_DIRECTORY)
                ->append(DIRECTORY_SEPARATOR)
                ->append($site->getHost(), '_', $site->getPort())
                ->append('.conf')
                ->toString(),
            $site->configuration()
        ));
    }

    private function publishHostNotFoundSite(): void
    {
        if (!$this->hostNotFoundPageEnabled()) {
            return;
        }

        $this->assets()->put(
            'build/host_not_found.html',
            view('services.nginx.misc.host_not_found_page')->with('service', $this)->render()
        );

        $this->assets()->put(
            self::ASSET_SITES_AVAILABLE_DIRECTORY.'/host_not_found.conf',
            view('services.nginx.misc.host_not_found_conf')->with('service', $this)->render()
        );
    }
}
