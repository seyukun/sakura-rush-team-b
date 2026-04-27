#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  02-install-packages.sh <container_id>

example:
  ./host/02-install-packages.sh wordpress
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

echo "[0/3] preflight"
cexec true

echo "[1/3] run container-side install.sh"
cexec bash /install.sh

echo "[2/3] run container-side prepare-container.sh"
cexec bash /prepare-container.sh

echo "[3/3] validate"
cexec php -v >/dev/null
cexec bash -lc 'php-fpm8.4 -t'
cexec nginx -t

echo "Package installation completed."
