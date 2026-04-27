#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  05-debug.sh <container_id>

example:
  ./host/05-debug.sh wordpress
EOF
    exit 1
}

if [ $# -ne 1 ]; then
    usage
fi

CTR_ID="$1"
SCRATCH="${SCRATCH:-${HOME}/scratch-container}"

cexec() {
    sudo "$SCRATCH" exec "$CTR_ID" "$@"
}

cexec_sh() {
    sudo "$SCRATCH" exec "$CTR_ID" bash -lc "$1"
}

echo "== processes =="
cexec_sh 'ps aux | egrep "mariadbd|php-fpm|nginx" | grep -v grep || true'

echo
echo "== sockets =="
cexec_sh 'ss -ltnp || true'

echo
echo "== wordpress =="
cexec_sh 'wp core is-installed --path=/var/www/html/wordpress --allow-root && echo installed || echo not-installed'

echo
for log in /tmp/mariadb.log /tmp/mariadb-init.log /tmp/php-fpm.log /tmp/nginx.log; do
    echo "== ${log} =="
    cexec_sh "test -f '${log}' && tail -n 80 '${log}' || echo no-log"
    echo
done
