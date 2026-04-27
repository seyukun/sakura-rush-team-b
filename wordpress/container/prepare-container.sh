#!/usr/bin/env bash
set -Eeuo pipefail

PHP_VERSION="${PHP_VERSION:-8.4}"

require_root() {
    if [ "$(id -u)" -ne 0 ]; then
        echo "Error: prepare-container.sh must be run as root." >&2
        exit 1
    fi
}

require_root

for f in \
    /install.sh \
    /prepare-container.sh \
    /start-detached.sh \
    /entrypoint-db.sh \
    /entrypoint-wp-init.sh \
    /entrypoint-php.sh \
    /entrypoint-nginx.sh
do
    if [ -e "$f" ]; then
        chown root:root "$f"
        chmod 0755 "$f"
    fi
done

if [ -e /usr/local/bin/wp ]; then
    chown root:root /usr/local/bin/wp
    chmod 0755 /usr/local/bin/wp
fi

install -d -m 0755 -o root -g root /etc/nginx
install -d -m 0755 -o root -g root /etc/nginx/conf.d
install -d -m 0755 -o root -g root /etc/mysql
install -d -m 0755 -o root -g root /etc/mysql/mariadb.conf.d

if [ -e /etc/nginx/conf.d/default.conf ]; then
    chown root:root /etc/nginx/conf.d/default.conf
    chmod 0644 /etc/nginx/conf.d/default.conf
fi

if [ -e /etc/mysql/mariadb.conf.d/90-container.cnf ]; then
    chown root:root /etc/mysql/mariadb.conf.d/90-container.cnf
    chmod 0644 /etc/mysql/mariadb.conf.d/90-container.cnf
fi

install -d -m 0755 -o mysql -g mysql /run/mysqld
install -d -m 0755 -o mysql -g mysql /var/lib/mysql

# Important for copied rootfs:
# When a rootfs that has already completed 02-install-packages.sh is copied on
# the host, numeric ownership inside /var/lib/mysql can become wrong for the
# new container/user namespace. MariaDB then fails with:
#   aria_log_control Permission denied
#   ibdata1 must be writable
# Always repair the data directory ownership from inside the container.
if [ -d /var/lib/mysql ]; then
    chown -R mysql:mysql /var/lib/mysql
    find /var/lib/mysql -type d -exec chmod u+rwx {} +
    find /var/lib/mysql -type f -exec chmod u+rw {} +
fi

install -d -m 0755 -o www-data -g www-data /run/php
install -d -m 0755 -o www-data -g www-data /var/www/html
install -d -m 0755 -o www-data -g www-data /var/www/html/wordpress

if [ -d /var/www/html/wordpress ]; then
    chown -R www-data:www-data /var/www/html/wordpress
fi

if [ -d "/etc/php/${PHP_VERSION}/fpm" ]; then
    php-fpm"${PHP_VERSION}" -t
fi

if command -v nginx >/dev/null 2>&1; then
    nginx -t
fi

echo "prepare-container.sh completed."
