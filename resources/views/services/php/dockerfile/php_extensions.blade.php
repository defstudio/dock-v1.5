{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
# PHP Extensions installation
RUN \
@if($service->phpExtensions()->contains('gd'))
@if($service->phpMajorVersion() < 7)
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
@else
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
@endif
@endif
@foreach($service->phpExtensions() as $ext)
    docker-php-ext-install {!! $ext !!}{!! $loop->last ? '' : " && \\" !!}
@endforeach
