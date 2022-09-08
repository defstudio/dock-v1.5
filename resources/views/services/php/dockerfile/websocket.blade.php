{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
#############
# WEBSOCKET #
#############

FROM base_php as websocket

COPY ./websocket/start_script.sh /usr/local/bin/start
RUN chmod 777 /usr/local/bin/start

CMD ["/usr/local/bin/start"]
