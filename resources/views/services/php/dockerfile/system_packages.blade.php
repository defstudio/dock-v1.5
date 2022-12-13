{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
# Packages installation

RUN apt-get update && \
@foreach($service->systemPackages() as $package)
    apt-get install -y --no-install-recommends {!! $package !!}{!! $loop->last ? '' : " && \\" !!}
@endforeach

@if($service->isMySqlClientEnabled())
    RUN apt-get update && apt-get install -y --no-install-recommends wget lsb-release && \
    wget -O mysql-apt-config.deb  https://dev.mysql.com/get/mysql-apt-config_0.8.24-1_all.deb && \
    DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config.deb && \
    apt-get update && \
    apt-get install -y --no-install-recommends mysql-client && \
    rm mysql-apt-config.deb; \
@endif
