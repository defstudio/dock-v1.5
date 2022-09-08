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
use App\Facades\Env;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Nginx extends Service
{
    protected const LETSENCRYPT_FOLDER = '/etc/letsencrypt';

    protected const CONF_PATH = '/nginx.conf';

    protected const SITES_PATH = '/sites-available';

    protected string $phpService;

    /** @var Collection<string, Site> */
    protected Collection $sites;

    protected bool $enableProxyTargetNotFoundPage = false;

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
                'context' => $this->assetsFolder(),
            ],
        ]);

        if ($this->isDockerHostExposed()) {
            $this->serviceDefinition->push('extra_hosts', 'host.docker.internal:host-gateway');
        }

        $this->addVolume(self::HOST_SRC_PATH, $this->getWorkingDir());
        $this->addVolume(self::HOST_SERVICES_PATH."/$this->name".self::CONF_PATH, '/etc/nginx/'.self::CONF_PATH);
        $this->addVolume(self::HOST_SERVICES_PATH."/$this->name".self::SITES_PATH, '/etc/nginx/'.self::SITES_PATH);

        $this->setupSite();

        $this->addNetwork($this->internalNetworkName());

        if ($this->isBehindReverseProxy()) {
            $this->addNetwork($this->reverseProxyNexwork())->external();
        }
    }

    public function phpService(Php $php): static
    {
        $this->phpService = $php->name();
        $this->serviceDefinition->push('depends_on', $php->name());

        return $this;
    }

    protected function setExternalCertificate(): void
    {
        $externalCertificatesFolder = Env::get('NGINX_EXTERNAL_CERTIFICATE_FOLDER');

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
        $port = (int) Env::get('NGINX_PORT', 80);

        if ($port === 443) {
            $this->setupSslSite();

            return;
        }

        $this->addSite($this->host(), $port)
            ->root($this->getWorkingDir())
            ->proxyWebsocket((bool) Env::get('WEBSOCKET_ENABLED'));
    }

    protected function setupSslSite(): void
    {
        $this->setExternalCertificate();

        if (Env::get('NGINX_EXTERNAL_CERTIFICATE_FOLDER')) {
            $certificateHostname = (string) Env::get('NGINX_EXTERNAL_CERTIFICATE_HOSTNAME', $this->host());

            $sslCertificate = self::LETSENCRYPT_FOLDER."/live/$certificateHostname/fullchain.pem";
            $sslCertificateKey = self::LETSENCRYPT_FOLDER."/live/$certificateHostname/privkey.pem";
        }

        $this->addSite($this->host(), 443)
            ->root($this->getWorkingDir())
            ->certificatePath($sslCertificate ?? self::LETSENCRYPT_FOLDER."/live/{$this->host()}/fullchain.pem")
            ->certificateKeyPath($sslCertificateKey ?? self::LETSENCRYPT_FOLDER."/live/{$this->host()}/privkey.pem")
            ->proxyWebsocket((bool) Env::get('WEBSOCKET_ENABLED'));
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

    public function enableProxyTargetNotFoundPage(bool $enable = true): static
    {
        $this->enableProxyTargetNotFoundPage = $enable;

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

    public function isProxyTargetNotFoundPageEnabled(): bool
    {
        return $this->enableProxyTargetNotFoundPage;
    }
}
