{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
############
# COMPOSER #
############

FROM base_php as composer

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/bin --filename=composer

RUN mkdir -p /.composer/cache && chmod -R 777 /.composer/cache

@include('services.php.dockerfile.pcov', ['service' => $service])

COPY ./composer/start_script.sh /usr/local/bin/start
RUN chmod 777 /usr/local/bin/start

CMD ["/usr/local/bin/start"]
