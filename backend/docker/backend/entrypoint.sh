#!/usr/bin/env sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [ -f artisan ]; then
    php artisan config:clear --no-interaction || true
    php artisan route:clear --no-interaction || true
    php artisan view:clear --no-interaction || true
fi

exec "$@"
