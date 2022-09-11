<?php

declare(strict_types=1);

use App\Docker\Services\MailHog;
use App\Docker\Services\Nginx;
use App\Facades\Env;

beforeEach(function () {
    Env::fake(['RECIPE' => 'test-recipe', 'HOST' => 'bar.ktm']);
});

it('can set its service name', function () {
    expect(new MailHog())->name()->toBe('mailhog');
});

it('sets its yml', function () {
    expect(new MailHog())->yml()->toMatchSnapshot();
});

it('can set its port', function () {
    Env::put('MAILHOG_PORT', 99);
    expect(new MailHog())
        ->yml('ports')->toBe(['99:8025'])
        ->yml('expose')->toBe([8025, 1025]);
});

it('add internal network', function () {
    expect(new MailHog())->toHaveNetwork('bar.ktm_internal_network');
});

it('adds its subdomain to Nginx service', function () {
    Env::put('MAILHOG_SUBDOMAIN', 'foo');

    $nginx = new Nginx();
    $mailhog = new MailHog();

    $mailhog->nginxService($nginx);

    $site = $nginx->getSite('foo.bar.ktm');

    expect($site->configuration())->toMatchTextSnapshot();
});

test('commands', function () {
    expect(new MailHog())->commands()->toBe([]);
});
