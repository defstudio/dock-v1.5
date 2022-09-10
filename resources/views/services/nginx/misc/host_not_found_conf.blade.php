{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Nginx $service */ ?>
# Default
server {
    listen 80 default_server;
    listen 443 default_server;

    ssl_certificate /etc/nginx/ssl/nginx.cert;
    ssl_certificate_key /etc/nginx/ssl/nginx.key;

    server_name _;
    root /var/host_not_found;

    charset UTF-8;

    error_page 404 /index.html;

    location = /index.html {
        allow   all;
    }

    location / {
        return 404;
    }


    #access_log off;
    #log_not_found off;

    error_log  /var/log/nginx/error.log error;
}
