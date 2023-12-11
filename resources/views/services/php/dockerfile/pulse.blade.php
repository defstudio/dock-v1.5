{{--@formatter:off--}}
<?php /** @var \App\Docker\Services\Php $service */ ?>
###########
#  PULSE  #
###########

FROM base_php as pulse

COPY ./pulse/start_script.sh /usr/local/bin/start
RUN chmod 777 /usr/local/bin/start

CMD ["/usr/local/bin/start"]
