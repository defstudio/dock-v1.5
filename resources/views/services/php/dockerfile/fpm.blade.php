{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
#######
# FPM #
#######

FROM base_php as fpm

@include('services.php.dockerfile.xdebug', ['service' => $service])

@include('services.php.dockerfile.pcov', ['service' => $service])
