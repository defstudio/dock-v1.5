{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Nginx $service */ ?>
upstream php-upstream {
    server {{$service->getPhpService()->name()}}:9000;
}

