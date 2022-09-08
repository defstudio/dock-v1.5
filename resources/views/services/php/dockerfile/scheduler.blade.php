{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
<?php /** @var \App\Docker\Services\Php $service */ ?>
#############
# SCHEDULER #
#############

FROM base_php as scheduler

COPY ./scheduler/start_script.sh /usr/local/bin/start
RUN chmod 777 /usr/local/bin/start

CMD ["/usr/local/bin/start"]
