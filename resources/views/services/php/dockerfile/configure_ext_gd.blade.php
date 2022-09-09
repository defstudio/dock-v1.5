{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
@if($service->phpExtensions()->contains('gd'))
@if($service->getPhpMinorVersion() < 7.4)
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
@else
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
@endif
@endif
