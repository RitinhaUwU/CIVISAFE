#!/bin/sh
set -e

php /app/artisan app:bootstrap

exec "$@"
