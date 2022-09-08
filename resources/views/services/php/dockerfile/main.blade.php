{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
FROM php:{{$service->getPhpVersion()==='latest' ? 'fpm' : "{$service->getPhpVersion()}-fpm"}} as base_php

LABEL org.opencontainers.image.created="{{today()}}"
LABEL org.opencontainers.image.authors="def:studio (https://github.com/def-studio)"

@include('services.php.dockerfile.system_packages', ['service' => $service])

@include('services.php.dockerfile.libreoffice', ['service' => $service])

@include('services.php.dockerfile.php_extensions', ['service' => $service])

@include('services.php.dockerfile.redis', ['service' => $service])

@include('services.php.dockerfile.ensure_psysh_is_writable', ['service' => $service])

COPY php.ini "$PHP_INI_DIR/php.ini"

@include('services.php.dockerfile.fpm', ['service' => $service])


