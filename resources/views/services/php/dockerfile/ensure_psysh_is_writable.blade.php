{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
# Ensure psysh file is writable
RUN mkdir -p /.config/psysh && chmod -R 777 /.config/psysh
