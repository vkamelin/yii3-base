#!/usr/bin/env sh
set -eu

mkdir -p /app/runtime /app/runtime/cache /app/runtime/logs /app/runtime/logs/supervisor /app/runtime/logs/php-fpm
chown -R www-data:www-data /app/runtime

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
