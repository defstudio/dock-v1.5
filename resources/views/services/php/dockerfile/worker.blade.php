{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
##########
# WORKER #
##########

FROM base_php as worker

COPY ./worker/start_script.sh /usr/local/bin/start
RUN chmod 777 /usr/local/bin/start

CMD ["/usr/local/bin/start"]
