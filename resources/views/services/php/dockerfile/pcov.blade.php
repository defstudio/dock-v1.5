{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
@if($service->isPcovEnabled())
# Enable PCov (https://github.com/krakjoe/pcov)
RUN pecl install pcov && \
    docker-php-ext-enable pcov
@endif
