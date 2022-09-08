{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
@if($service->isLibreOfficeWriterEnabled())
# LibreOffice Writer installation
RUN mkdir -p /usr/share/man/man1 \
    && mkdir -p /.cache/dconf && chmod -R 777 /.cache/dconf \
    && apt-get update \
    && apt-get install -y --no-install-recommends openjdk-11-jre-headless \
    && apt-get install -y --no-install-recommends libreoffice-writer \
    && apt-get install -y --no-install-recommends libreoffice-java-common \
    && apt-get install -y --no-install-recommends pandoc
@endif
