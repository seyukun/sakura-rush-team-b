#!/usr/bin/env bash
set -Eeuo pipefail

PHP_VERSION="${PHP_VERSION:-8.4}"

install -d -m 0755 -o www-data -g www-data /run/php
install -d -m 0755 -o www-data -g www-data /var/www/html

if [ "$#" -eq 0 ]; then
    set -- "php-fpm${PHP_VERSION}" -F
fi

exec "$@"
