#!/usr/bin/env bash
set -Eeuo pipefail

WORDPRESS_PATH="${WORDPRESS_PATH:-/var/www/html/wordpress}"
WORDPRESS_DB_HOST="${WORDPRESS_DB_HOST:-127.0.0.1}"
WP_LOCALE="${WP_LOCALE:-ja}"
WP_TITLE="${WP_TITLE:-WordPress}"
WP_ROLE="${WP_ROLE:-author}"
WP_MEMORY_LIMIT="${WP_MEMORY_LIMIT:-128M}"

wait_for_db() {
    for _ in $(seq 1 90); do
        if mariadb \
            -h "$WORDPRESS_DB_HOST" \
            -u "$MARIADB_USER" \
            -p"$MARIADB_PASSWORD" \
            "$MARIADB_DATABASE" \
            -e 'SELECT 1' >/dev/null 2>&1; then
            return 0
        fi

        sleep 1
    done

    echo "Error: DB connection failure." >&2
    return 1
}

: "${MARIADB_DATABASE:?MARIADB_DATABASE is required}"
: "${MARIADB_USER:?MARIADB_USER is required}"
: "${MARIADB_PASSWORD:?MARIADB_PASSWORD is required}"

: "${WP_URL:?WP_URL is required. Example: http://10.200.1.200}"
: "${WP_ADMIN:?WP_ADMIN is required}"
: "${WP_ADMIN_PASSWORD:?WP_ADMIN_PASSWORD is required}"
: "${WP_ADMIN_EMAIL:?WP_ADMIN_EMAIL is required}"

wait_for_db

install -d -m 0755 -o www-data -g www-data /var/www/html
install -d -m 0755 -o www-data -g www-data "$WORDPRESS_PATH"

if [ ! -e "${WORDPRESS_PATH}/wp-includes/version.php" ]; then
    echo "Downloading WordPress core..."

    wp core download \
        --locale="$WP_LOCALE" \
        --path="$WORDPRESS_PATH" \
        --allow-root

    chown -R www-data:www-data "$WORDPRESS_PATH"
fi

if [ ! -f "${WORDPRESS_PATH}/wp-config.php" ]; then
    echo "Creating wp-config.php..."

    wp config create \
        --dbname="$MARIADB_DATABASE" \
        --dbuser="$MARIADB_USER" \
        --dbpass="$MARIADB_PASSWORD" \
        --dbhost="$WORDPRESS_DB_HOST" \
        --dbcharset="utf8mb4" \
        --locale="$WP_LOCALE" \
        --path="$WORDPRESS_PATH" \
        --allow-root

    wp config set FS_METHOD direct \
        --type=constant \
        --path="$WORDPRESS_PATH" \
        --allow-root

    wp config set WP_MEMORY_LIMIT "$WP_MEMORY_LIMIT" \
        --type=constant \
        --path="$WORDPRESS_PATH" \
        --allow-root

    wp config shuffle-salts \
        --path="$WORDPRESS_PATH" \
        --allow-root

    chown www-data:www-data "${WORDPRESS_PATH}/wp-config.php"
    chmod 0640 "${WORDPRESS_PATH}/wp-config.php"
fi

if ! wp core is-installed --path="$WORDPRESS_PATH" --allow-root >/dev/null 2>&1; then
    echo "Installing WordPress..."

    wp core install \
        --url="$WP_URL" \
        --title="$WP_TITLE" \
        --admin_user="$WP_ADMIN" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --locale="$WP_LOCALE" \
        --skip-email \
        --path="$WORDPRESS_PATH" \
        --allow-root
fi

WP_DISPLAYNAME="${WP_DISPLAYNAME:-${WP_DISPLYNAME:-}}"

if [ -n "${WP_USERNAME:-}" ] || [ -n "${WP_EMAIL:-}" ] || [ -n "${WP_PASSWORD:-}" ] || [ -n "${WP_DISPLAYNAME:-}" ]; then
    : "${WP_USERNAME:?WP_USERNAME is required when creating a WordPress user}"
    : "${WP_EMAIL:?WP_EMAIL is required when creating a WordPress user}"
    : "${WP_PASSWORD:?WP_PASSWORD is required when creating a WordPress user}"
    : "${WP_DISPLAYNAME:?WP_DISPLAYNAME is required when creating a WordPress user}"

    if ! wp user get "$WP_USERNAME" --path="$WORDPRESS_PATH" --allow-root >/dev/null 2>&1; then
        wp user create "$WP_USERNAME" "$WP_EMAIL" \
            --role="$WP_ROLE" \
            --user_pass="$WP_PASSWORD" \
            --display_name="$WP_DISPLAYNAME" \
            --path="$WORDPRESS_PATH" \
            --allow-root
    fi
fi

install -d -m 0775 -o www-data -g www-data "${WORDPRESS_PATH}/wp-content"
install -d -m 0775 -o www-data -g www-data "${WORDPRESS_PATH}/wp-content/uploads"

chown -R www-data:www-data "$WORDPRESS_PATH"

echo "WordPress initialization completed."
