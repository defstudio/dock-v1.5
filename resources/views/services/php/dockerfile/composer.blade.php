{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
############
# COMPOSER #
############

FROM base_php as composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /.composer/cache && chmod -R 777 /.composer/cache

@include('services.php.dockerfile.pcov', ['service' => $service])
