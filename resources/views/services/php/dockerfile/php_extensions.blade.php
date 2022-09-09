{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
# PHP Extensions installation
RUN \
@include('services.php.dockerfile.configure_ext_gd', ['service' => $service])
@foreach($service->phpExtensions() as $ext)
    docker-php-ext-install {!! $ext !!}{!! $loop->last ? '' : " && \\" !!}
@endforeach
