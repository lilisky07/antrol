#!/bin/sh
set -e

echo "Starting Laravel..."

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"