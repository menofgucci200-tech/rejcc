#!/bin/sh
set -e

PORT="${PORT:-10000}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan storage:link || true
php artisan migrate --force

exec apache2-foreground
