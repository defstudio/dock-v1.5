{{--@formatter:off--}}
#!/usr/bin/env bash

echo "Running Pulse service..."
php /var/www/artisan pulse:check
