#!/usr/bin/env bash
set -Eeuo pipefail

install -d -m 0755 -o www-data -g www-data /var/www/html

if [ "$#" -eq 0 ]; then
    set -- nginx -g 'daemon off;'
fi

nginx -t

exec "$@"
