<?php

use App\Docker\Site;

beforeEach(function () {
    $this->site = new Site('foo.com', 99);
});

it('can set its root', function () {
    $this->site->root('/var/baz');

    expect($this->site)->getRoot()->toBe('/var/baz');
});

it('can set its certificate path', function () {
    $this->site->certificatePath('/var/baz/certificates/cert.foo');

    expect($this->site)->getCertificatePath()->toBe('/var/baz/certificates/cert.foo');
});

it('can set its certificate key path', function () {
    $this->site->certificateKeyPath('/var/baz/certificates/key.baz');

    expect($this->site)->getCertificateKeyPath()->toBe('/var/baz/certificates/key.baz');
});

it('can enable websockets proxy', function () {
    expect($this->site)->shouldProxyWebsocket()->toBeFalse();

    $this->site->proxyWebsocket();

    expect($this->site)->shouldProxyWebsocket()->toBeTrue();
});

it('can add proxy data', function () {
    $this->site->proxy('bar.net', 99);

    expect($this->site)
        ->getProxyTarget()->toBe('bar.net')
        ->getProxyPort()->toBe(99);
});

it('can render its configuration', function (Site $site) {
    expect($site->configuration())->toMatchTextSnapshot();
})->with([
    'standard' => ['site' => (new Site('foo.com', 42))],
    'custom root' => ['site' => (new Site('foo.com', 42))->root('/foo/bar/zap')],
    'websockets proxy' => ['site' => (new Site('foo.com', 42))->proxyWebsocket()],
    'proxy' => ['site' => (new Site('foo.com', 42))->proxy('zap.net', 87)],
    'ssl' => ['site' => (new Site('foo.com', 443))->certificatePath('foo/key')->certificateKeyPath('foo/key/bar')],
    'ssl websockets proxy' => ['site' => (new Site('foo.com', 443))->certificatePath('foo/key')->certificateKeyPath('foo/key/bar')->proxyWebsocket()],
    'ssl proxy' => ['site' => (new Site('foo.com', 443))->certificatePath('foo/key')->certificateKeyPath('foo/key/bar')->proxy('puk.qux', 42)],
]);
