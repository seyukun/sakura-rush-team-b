#!/usr/bin/env bash
set -Eeuo pipefail

MARIADB_SOCKET="${MARIADB_SOCKET:-/run/mysqld/mysqld.sock}"
PROVISION_MARKER="/var/lib/mysql/.wordpress-provisioned"

validate_identifier() {
    local name="$1"
    local value="$2"

    if [[ ! "$value" =~ ^[A-Za-z0-9_]+$ ]]; then
        echo "Error: ${name} must match ^[A-Za-z0-9_]+$" >&2
        exit 1
    fi
}

sql_escape() {
    local value="$1"
    value="${value//\'/\'\'}"
    printf '%s' "$value"
}

wait_for_socket_mariadb() {
    local socket="$1"
    local pid="$2"
    local log_file="$3"

    for _ in $(seq 1 90); do
        if mariadb-admin --protocol=socket --socket="$socket" ping --silent >/dev/null 2>&1; then
            return 0
        fi

        if ! kill -0 "$pid" >/dev/null 2>&1; then
            echo "Error: temporary mariadbd exited unexpectedly." >&2
            cat "$log_file" >&2 || true
            return 1
        fi

        sleep 1
    done

    echo "Error: temporary mariadbd did not become ready." >&2
    cat "$log_file" >&2 || true
    return 1
}

run_root_sql_file() {
    local socket="$1"
    local sql_file="$2"

    # Debian/MariaDB packages often create root@localhost with unix_socket auth.
    # Fresh mariadb-install-db with auth-root-authentication-method=normal usually allows
    # passwordless local root until we set the password below.
    if mariadb --protocol=socket --socket="$socket" -uroot <"$sql_file"; then
        return 0
    fi

    # If a previous run partially set the root password but did not write the marker,
    # this fallback makes provisioning idempotently recoverable.
    mariadb --protocol=socket --socket="$socket" -uroot -p"${MARIADB_ROOT_PASSWORD}" <"$sql_file"
}

: "${MARIADB_ROOT_PASSWORD:?MARIADB_ROOT_PASSWORD is required}"
: "${MARIADB_DATABASE:?MARIADB_DATABASE is required}"
: "${MARIADB_USER:?MARIADB_USER is required}"
: "${MARIADB_PASSWORD:?MARIADB_PASSWORD is required}"

validate_identifier "MARIADB_DATABASE" "$MARIADB_DATABASE"
validate_identifier "MARIADB_USER" "$MARIADB_USER"

ROOT_PASS_SQL="$(sql_escape "$MARIADB_ROOT_PASSWORD")"
DB_PASS_SQL="$(sql_escape "$MARIADB_PASSWORD")"

install -d -m 0755 -o mysql -g mysql /run/mysqld
install -d -m 0755 -o mysql -g mysql /var/lib/mysql

# Repair ownership every time because this project commonly clones rootfs after
# package installation. Host-side copies can preserve/translate numeric owners
# in a way that does not match the new container's mysql user.
chown -R mysql:mysql /var/lib/mysql
find /var/lib/mysql -type d -exec chmod u+rwx {} +
find /var/lib/mysql -type f -exec chmod u+rw {} +

if [ "$#" -eq 0 ] || [ "${1:0:1}" = "-" ]; then
    set -- mariadbd "$@"
fi

if [ "$1" != "mariadbd" ] && [ "$1" != "mysqld" ]; then
    exec "$@"
fi

if [ ! -d /var/lib/mysql/mysql ]; then
    echo "Initializing MariaDB system tables..."

    rm -f /var/lib/mysql/aria_log_control
    rm -f /var/lib/mysql/aria_log.*
    rm -f /var/lib/mysql/ib_logfile*
    rm -f /var/lib/mysql/ibdata1

    mariadb-install-db \
        --user=mysql \
        --datadir=/var/lib/mysql \
        --skip-test-db \
        --auth-root-authentication-method=normal
fi

if [ ! -f "$PROVISION_MARKER" ]; then
    echo "Provisioning MariaDB users and WordPress database..."

    TEMP_SOCKET="/run/mysqld/mysqld-init.sock"
    TEMP_PIDFILE="/run/mysqld/mysqld-init.pid"
    TEMP_LOG="/tmp/mariadb-init.log"
    TEMP_SQL="$(mktemp /tmp/mariadb-provision.XXXXXX.sql)"
    trap 'rm -f "$TEMP_SQL"; if [ -n "${TEMP_PID:-}" ] && kill -0 "$TEMP_PID" >/dev/null 2>&1; then kill "$TEMP_PID" >/dev/null 2>&1 || true; wait "$TEMP_PID" >/dev/null 2>&1 || true; fi' EXIT

    gosu mysql mariadbd \
        --datadir=/var/lib/mysql \
        --skip-networking \
        --socket="$TEMP_SOCKET" \
        --pid-file="$TEMP_PIDFILE" \
        --log-error="$TEMP_LOG" &

    TEMP_PID="$!"

    wait_for_socket_mariadb "$TEMP_SOCKET" "$TEMP_PID" "$TEMP_LOG"

    cat >"$TEMP_SQL" <<EOSQL
SET @@SESSION.SQL_LOG_BIN = 0;
SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES';

DROP DATABASE IF EXISTS test;

CREATE DATABASE IF NOT EXISTS \`${MARIADB_DATABASE}\`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE OR REPLACE USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASS_SQL}';
CREATE OR REPLACE USER 'root'@'127.0.0.1' IDENTIFIED BY '${ROOT_PASS_SQL}';

GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;

CREATE OR REPLACE USER '${MARIADB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS_SQL}';
CREATE OR REPLACE USER '${MARIADB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS_SQL}';

GRANT ALL PRIVILEGES ON \`${MARIADB_DATABASE}\`.* TO '${MARIADB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${MARIADB_DATABASE}\`.* TO '${MARIADB_USER}'@'127.0.0.1';

FLUSH PRIVILEGES;
EOSQL

    run_root_sql_file "$TEMP_SOCKET" "$TEMP_SQL"

    mariadb-admin \
        --protocol=socket \
        --socket="$TEMP_SOCKET" \
        -uroot \
        -p"${MARIADB_ROOT_PASSWORD}" \
        shutdown

    wait "$TEMP_PID"
    TEMP_PID=""

    rm -f "$TEMP_SQL"
    trap - EXIT

    chown -R mysql:mysql /var/lib/mysql
    touch "$PROVISION_MARKER"
    chown mysql:mysql "$PROVISION_MARKER"

    echo "MariaDB provisioning completed."
fi

exec gosu mysql "$@"
