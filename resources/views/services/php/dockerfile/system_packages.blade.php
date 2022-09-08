{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
# Packages installation
RUN apt-get update && \
@foreach($service->systemPackages() as $package)
    apt-get install -y --no-install-recommends {!! $package !!}{!! $loop->last ? '' : " && \\" !!}
@endforeach
