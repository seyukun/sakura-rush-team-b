#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  03-start-wordpress.sh <container_id>

environment variables:
  SCRATCH                  default: ${HOME}/scratch-container
  PHP_VERSION              default: 8.4

  MARIADB_ROOT_PASSWORD    default: i_am_root
  MARIADB_DATABASE         default: i_am_database
  MARIADB_USER             default: i_am_user
  MARIADB_PASSWORD         default: i_am_user

  WP_URL                   default: http://10.200.1.200
  WP_TITLE                 default: title
  WP_ADMIN                 default: i_im_admin
  WP_ADMIN_PASSWORD        default: i_im_admin
  WP_ADMIN_EMAIL           default: i-im-admin@example.local

  WP_USERNAME              optional; default: i_im_user
  WP_EMAIL                 optional; default: i-im-user@example.local
  WP_PASSWORD              optional; default: i_im_user
  WP_DISPLAYNAME           optional; default: i-im-user

example:
  WP_URL=http://10.200.1.200 ./host/03-start-wordpress.sh wordpress
EOF
    exit 1
}

if [ $# -ne 1 ]; then
    usage
fi

CTR_ID="$1"
SCRATCH="${SCRATCH:-${HOME}/scratch-container}"

PHP_VERSION="${PHP_VERSION:-8.4}"

MARIADB_ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:-i_am_root}"
MARIADB_DATABASE="${MARIADB_DATABASE:-i_am_database}"
MARIADB_USER="${MARIADB_USER:-i_am_user}"
MARIADB_PASSWORD="${MARIADB_PASSWORD:-i_am_user}"

WP_URL="${WP_URL:-http://10.200.1.200}"
WP_TITLE="${WP_TITLE:-title}"
WP_ADMIN="${WP_ADMIN:-i_im_admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-i_im_admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-i-im-admin@example.local}"

WP_USERNAME="${WP_USERNAME:-i_im_user}"
WP_EMAIL="${WP_EMAIL:-i-im-user@example.local}"
WP_PASSWORD="${WP_PASSWORD:-i_im_user}"
WP_DISPLAYNAME="${WP_DISPLAYNAME:-i-im-user}"

cexec() {
    sudo "$SCRATCH" exec "$CTR_ID" "$@"
}

cexec_sh() {
    sudo "$SCRATCH" exec "$CTR_ID" bash -lc "$1"
}

log() {
    printf '%s\n' "$*"
}

show_log_tail() {
    local file="$1"
    cexec bash -lc "test -f '$file' && tail -n 200 '$file' || true" >&2 || true
}

wait_for_db() {
    log "[wait] MariaDB"

    for _ in $(seq 1 90); do
        if cexec mariadb \
            -h 127.0.0.1 \
            -u"${MARIADB_USER}" \
            -p"${MARIADB_PASSWORD}" \
            "${MARIADB_DATABASE}" \
            -e 'SELECT 1' >/dev/null 2>&1; then
            return 0
        fi

        sleep 1
    done

    echo "Error: MariaDB did not become ready." >&2
    show_log_tail /tmp/mariadb.log
    exit 1
}

wait_for_php() {
    log "[wait] php-fpm"

    for _ in $(seq 1 60); do
        if cexec_sh "ss -ltn | grep -q '127.0.0.1:9000'"; then
            return 0
        fi

        sleep 1
    done

    echo "Error: php-fpm did not become ready." >&2
    show_log_tail /tmp/php-fpm.log
    exit 1
}

wait_for_nginx() {
    log "[wait] nginx"

    for _ in $(seq 1 60); do
        if cexec curl -fsSI http://127.0.0.1/ >/dev/null 2>&1; then
            return 0
        fi

        sleep 1
    done

    echo "Error: nginx did not become ready." >&2
    show_log_tail /tmp/nginx.log
    exit 1
}

prepare_inside_container() {
    log "[1/6] prepare permissions inside container"
    cexec env "PHP_VERSION=${PHP_VERSION}" bash /prepare-container.sh
}

stop_existing_services() {
    log "[2/6] stop existing services"

    cexec env "MARIADB_ROOT_PASSWORD=${MARIADB_ROOT_PASSWORD}" bash -lc '
        mariadb-admin -h 127.0.0.1 -uroot -p"$MARIADB_ROOT_PASSWORD" shutdown >/dev/null 2>&1 || true
        pkill -x nginx >/dev/null 2>&1 || true
        pkill -x php-fpm'"${PHP_VERSION}"' >/dev/null 2>&1 || true
        pkill -x mariadbd >/dev/null 2>&1 || true
        pkill -x mysqld >/dev/null 2>&1 || true
    '

    sleep 2
}

start_db() {
    log "[3/6] start MariaDB"

    cexec env \
        "MARIADB_ROOT_PASSWORD=${MARIADB_ROOT_PASSWORD}" \
        "MARIADB_DATABASE=${MARIADB_DATABASE}" \
        "MARIADB_USER=${MARIADB_USER}" \
        "MARIADB_PASSWORD=${MARIADB_PASSWORD}" \
        bash /start-detached.sh mariadb bash /entrypoint-db.sh mariadbd

    wait_for_db
}

init_wordpress() {
    log "[4/6] initialize WordPress"

    cexec env \
        "MARIADB_DATABASE=${MARIADB_DATABASE}" \
        "MARIADB_USER=${MARIADB_USER}" \
        "MARIADB_PASSWORD=${MARIADB_PASSWORD}" \
        "WP_URL=${WP_URL}" \
        "WP_TITLE=${WP_TITLE}" \
        "WP_ADMIN=${WP_ADMIN}" \
        "WP_ADMIN_PASSWORD=${WP_ADMIN_PASSWORD}" \
        "WP_ADMIN_EMAIL=${WP_ADMIN_EMAIL}" \
        "WP_USERNAME=${WP_USERNAME}" \
        "WP_EMAIL=${WP_EMAIL}" \
        "WP_PASSWORD=${WP_PASSWORD}" \
        "WP_DISPLAYNAME=${WP_DISPLAYNAME}" \
        "WP_MEMORY_LIMIT=128M" \
        bash /entrypoint-wp-init.sh
}

start_php() {
    log "[5/6] start php-fpm"

    cexec env \
        "PHP_VERSION=${PHP_VERSION}" \
        bash /start-detached.sh php-fpm bash /entrypoint-php.sh "php-fpm${PHP_VERSION}" -F

    wait_for_php
}

start_nginx() {
    log "[6/6] start nginx"

    cexec bash /start-detached.sh nginx bash /entrypoint-nginx.sh nginx -g 'daemon off;'

    wait_for_nginx
}

print_summary() {
    echo
    echo "WordPress is running."
    echo "  URL: ${WP_URL}"
    echo
    echo "Logs:"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} tail -n 200 /tmp/mariadb.log"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} tail -n 200 /tmp/php-fpm.log"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} tail -n 200 /tmp/nginx.log"
    echo
    echo "Debug:"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} bash"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} wp core is-installed --path=/var/www/html/wordpress --allow-root"
    echo "  sudo ${SCRATCH} exec ${CTR_ID} curl -I http://127.0.0.1/"
}

log "[0/6] preflight"
cexec true

prepare_inside_container
stop_existing_services
start_db
init_wordpress
start_php
start_nginx
print_summary
