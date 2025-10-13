#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -f artisan ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec php-fpm
