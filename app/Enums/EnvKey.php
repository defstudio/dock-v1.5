<?php

namespace App\Enums;

enum EnvKey: string
{
    case env = 'ENV';
    case recipe = 'RECIPE';

    case host = 'HOST';
    case user_id = 'USER_ID';
    case group_id = 'GROUP_ID';

    case expose_docker_host = 'EXPOSE_DOCKER_HOST';

    case behind_proxy = 'BEHIND_PROXY';
    case reverse_proxy_network = 'REVERSE_PROXY_NETWORK';

    case nginx_port = 'NGINX_PORT';
    case nginx_external_certificate = 'NGINX_EXTERNAL_CERTIFICATE';
    case nginx_external_certificate_folder = 'NGINX_EXTERNAL_CERTIFICATE_FOLDER';
    case nginx_external_certificate_hostname = 'NGINX_EXTERNAL_CERTIFICATE_HOSTNAME';

    case php_version = 'PHP_VERSION';

    case extra_tools = 'EXTRA_TOOLS';

    case pulse_enabled = 'PULSE_ENABLED';

    case db_engine = 'DB_ENGINE';
    case db_port = 'DB_PORT';
    case db_name = 'DB_DATABASE';
    case db_user = 'DB_USER';
    case db_password = 'DB_PASSWORD';
    case db_root_password = 'DB_ROOT_PASSWORD';
    case db_disable_strict_mode = 'DB_DISABLE_STRICT_MODE';

    case phpmyadmin_enabled = 'PHPMYADMIN_ENABLED';
    case phpmyadmin_port = 'PHPMYADMIN_PORT';
    case phpmyadmin_subdomain = 'PHPMYADMIN_SUBDOMAIN';

    case mailhog_enabled = 'MAILHOG_ENABLED';
    case mailhog_port = 'MAILHOG_PORT';
    case mailhog_subdomain = 'MAILHOG_SUBDOMAIN';

    case redis_enabled = 'REDIS_ENABLED';
    case redis_version = 'REDIS_VERSION';
    case redis_password = 'REDIS_PASSWORD';
    case redis_persist_data = 'REDIS_PERSIST_DATA';
    case redis_snapshot_every_seconds = 'REDIS_SNAPSHOT_EVERY_SECONDS';
    case redis_snapshot_every_writes = 'REDIS_SNAPSHOT_EVERY_WRITES';

    case websocket_enabled = 'WEBSOCKET_ENABLED';
    case websocket_port = 'WEBSOCKET_PORT';

    case node_version = 'NODE_VERSION';

    case git_enabled = 'GIT_REPOSITORY_ENABLED';
    case git_repository = 'GIT_REPOSITORY';
    case git_branch = 'GIT_BRANCH';

    case foo = 'FOO';
    case bar = 'BAR';
    case baz = 'BAZ';
}
