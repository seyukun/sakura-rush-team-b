#!/usr/bin/env bash
set -Eeuo pipefail

export DEBIAN_FRONTEND=noninteractive
export LANG=C.UTF-8
export LC_ALL=C.UTF-8

PHP_VERSION="${PHP_VERSION:-8.4}"
PHP_PREFIX="php${PHP_VERSION}"

require_root() {
    if [ "$(id -u)" -ne 0 ]; then
        echo "Error: install.sh must be run as root." >&2
        exit 1
    fi
}

apt_install() {
    apt-get install -y --no-install-recommends \
        -o Dpkg::Options::=--force-confdef \
        -o Dpkg::Options::=--force-confold \
        "$@"
}

set_php_ini_value() {
    local file="$1"
    local key="$2"
    local value="$3"

    if grep -Eq "^[;[:space:]]*${key}[[:space:]]*=" "$file"; then
        sed -ri "s|^[;[:space:]]*${key}[[:space:]]*=.*|${key} = ${value}|" "$file"
    else
        printf '\n%s = %s\n' "$key" "$value" >> "$file"
    fi
}

cleanup() {
    rm -f /usr/sbin/policy-rc.d
    rm -f /tmp/debsuryorg-archive-keyring.deb
    rm -rf /var/lib/apt/lists/*
}

trap cleanup EXIT

require_root

cat > /usr/sbin/policy-rc.d <<'EOF_POLICY'
#!/bin/sh
exit 101
EOF_POLICY
chmod +x /usr/sbin/policy-rc.d

apt-get update
apt_install \
    locales \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    debian-archive-keyring \
    unzip \
    gosu \
    openssl \
    procps \
    psmisc \
    iproute2 \
    util-linux

if grep -qE '^# *ja_JP\.UTF-8 UTF-8' /etc/locale.gen; then
    sed -i 's/^# *ja_JP\.UTF-8 UTF-8/ja_JP.UTF-8 UTF-8/' /etc/locale.gen
elif ! grep -qE '^ja_JP\.UTF-8 UTF-8' /etc/locale.gen; then
    echo 'ja_JP.UTF-8 UTF-8' >> /etc/locale.gen
fi

locale-gen ja_JP.UTF-8
update-locale LANG=ja_JP.UTF-8 LANGUAGE=ja_JP:ja

export LANG=ja_JP.UTF-8
export LC_ALL=ja_JP.UTF-8
export LANGUAGE=ja_JP:ja

. /etc/os-release
DEBIAN_CODENAME="${VERSION_CODENAME:-$(lsb_release -sc)}"

case "$DEBIAN_CODENAME" in
    bullseye|bookworm|trixie)
        ;;
    *)
        echo "Error: unsupported or unknown Debian codename: ${DEBIAN_CODENAME}" >&2
        exit 1
        ;;
esac

curl -fsSL -o /tmp/debsuryorg-archive-keyring.deb \
    https://packages.sury.org/debsuryorg-archive-keyring.deb
dpkg -i /tmp/debsuryorg-archive-keyring.deb

cat > /etc/apt/sources.list.d/sury-php.list <<EOF_PHP_REPO
deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ ${DEBIAN_CODENAME} main
EOF_PHP_REPO

cat > /etc/apt/preferences.d/99php <<'EOF_PHP_PIN'
Package: php*
Pin: origin packages.sury.org
Pin-Priority: 900
EOF_PHP_PIN

curl -fsSL https://nginx.org/keys/nginx_signing.key \
    | gpg --dearmor -o /usr/share/keyrings/nginx-archive-keyring.gpg

if ! gpg --show-keys --with-colons /usr/share/keyrings/nginx-archive-keyring.gpg \
    | awk -F: '$1 == "fpr" { print $10 }' \
    | grep -qx '573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62'; then
    echo "Error: nginx signing key fingerprint verification failed." >&2
    exit 1
fi

cat > /etc/apt/sources.list.d/nginx.list <<EOF_NGINX_REPO
deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] https://nginx.org/packages/debian ${DEBIAN_CODENAME} nginx
EOF_NGINX_REPO

cat > /etc/apt/preferences.d/99nginx <<'EOF_NGINX_PIN'
Package: *
Pin: origin nginx.org
Pin: release o=nginx
Pin-Priority: 900
EOF_NGINX_PIN

apt-get update

apt_install \
    "${PHP_PREFIX}-cli" \
    "${PHP_PREFIX}-fpm" \
    "${PHP_PREFIX}-mysql" \
    "${PHP_PREFIX}-gd" \
    "${PHP_PREFIX}-mbstring" \
    "${PHP_PREFIX}-xml" \
    "${PHP_PREFIX}-curl" \
    "${PHP_PREFIX}-zip" \
    "${PHP_PREFIX}-opcache" \
    "${PHP_PREFIX}-intl" \
    "${PHP_PREFIX}-imagick" \
    "${PHP_PREFIX}-bcmath"

apt_install \
    imagemagick \
    ghostscript \
    mariadb-server \
    mariadb-client \
    nginx

PHP_FPM_POOL_CONF="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"

if [ ! -f "$PHP_FPM_POOL_CONF" ]; then
    echo "Error: PHP-FPM pool config not found: ${PHP_FPM_POOL_CONF}" >&2
    exit 1
fi

cp -an "$PHP_FPM_POOL_CONF" "${PHP_FPM_POOL_CONF}.orig"

sed -ri 's|^;?listen[[:space:]]*=.*|listen = 127.0.0.1:9000|' "$PHP_FPM_POOL_CONF"
sed -ri '/^;?listen\.(owner|group|mode)[[:space:]]*=/d' "$PHP_FPM_POOL_CONF"

set_php_ini_value "$PHP_FPM_POOL_CONF" "clear_env" "no"
set_php_ini_value "$PHP_FPM_POOL_CONF" "catch_workers_output" "yes"
set_php_ini_value "$PHP_FPM_POOL_CONF" "decorate_workers_output" "no"

for SAPI in cli fpm; do
    PHP_CONF_DIR="/etc/php/${PHP_VERSION}/${SAPI}/conf.d"

    cat > "${PHP_CONF_DIR}/99-wordpress-runtime.ini" <<'EOF_WORDPRESS_INI'
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
max_input_vars = 3000
expose_php = Off
date.timezone = Asia/Tokyo
EOF_WORDPRESS_INI
done

cat > "/etc/php/${PHP_VERSION}/fpm/conf.d/99-wordpress-opcache.ini" <<'EOF_OPCACHE'
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
opcache.save_comments = 1
EOF_OPCACHE

install -d -m 0755 -o www-data -g www-data /run/php
install -d -m 0755 -o mysql -g mysql /run/mysqld
install -d -m 0755 -o mysql -g mysql /var/lib/mysql
install -d -m 0755 -o www-data -g www-data /var/www/html

curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    -o /usr/local/bin/wp

chmod 0755 /usr/local/bin/wp
chown root:root /usr/local/bin/wp

wp --allow-root --info >/dev/null

php -v >/dev/null
php-fpm"${PHP_VERSION}" -t
nginx -t

# for sftp server
echo "Install SFTP Server"
apt_install openssh-server

apt-get clean

echo "install.sh completed."
