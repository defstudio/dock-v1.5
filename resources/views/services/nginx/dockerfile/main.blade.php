{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Nginx $service */ ?>
FROM nginx:stable

RUN mkdir /var/backend_not_found
COPY ./backend_not_found.html /var/backend_not_found/index.html

RUN apt-get update
RUN apt-get install -y --no-install-recommends openssl

RUN mkdir -p /etc/nginx/ssl/

RUN openssl req \
    -x509 \
    -subj "/C=IT/ST=Denial/L=Nowere/O=Dis" \
    -nodes \
    -days 365 \
    -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx.key \
    -out /etc/nginx/ssl/nginx.cert
