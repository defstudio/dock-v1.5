{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Node $service */ ?>
FROM node:{{$service->getNodeVersion()}}-alpine

# Makes .npm folder writable
RUN mkdir /.npm && \
    chmod -R 777 /.npm
