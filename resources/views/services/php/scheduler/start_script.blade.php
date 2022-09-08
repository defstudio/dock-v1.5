{{--@formatter:off--}}
#!/usr/bin/env bash

echo "Running Schedule Worker..."
php /var/www/artisan schedule:work > /dev/null
