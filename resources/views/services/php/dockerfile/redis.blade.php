{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
@if($service->isRedisEnabled())
# Enable Redis
RUN pecl install -o -f redis && \
    rm -rf /tmp/pear && \
    docker-php-ext-enable redis
@endif
