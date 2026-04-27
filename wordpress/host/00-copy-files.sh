#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  00-copy-files.sh <rootfs>

example:
  ./host/00-copy-files.sh ./rootfs
EOF
    exit 1
}

if [ $# -ne 1 ]; then
    usage
fi

ROOTFS="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUNDLE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
CONTAINER_DIR="${BUNDLE_DIR}/container"

if [ ! -d "$ROOTFS" ]; then
    echo "Error: rootfs not found: ${ROOTFS}" >&2
    exit 1
fi

cp "${CONTAINER_DIR}/install.sh" "${ROOTFS}/install.sh"
cp "${CONTAINER_DIR}/prepare-container.sh" "${ROOTFS}/prepare-container.sh"
cp "${CONTAINER_DIR}/start-detached.sh" "${ROOTFS}/start-detached.sh"
cp "${CONTAINER_DIR}/entrypoint-db.sh" "${ROOTFS}/entrypoint-db.sh"
cp "${CONTAINER_DIR}/entrypoint-wp-init.sh" "${ROOTFS}/entrypoint-wp-init.sh"
cp "${CONTAINER_DIR}/entrypoint-php.sh" "${ROOTFS}/entrypoint-php.sh"
cp "${CONTAINER_DIR}/entrypoint-nginx.sh" "${ROOTFS}/entrypoint-nginx.sh"

mkdir -p "${ROOTFS}/etc/nginx/conf.d"
mkdir -p "${ROOTFS}/etc/mysql/mariadb.conf.d"

cp "${CONTAINER_DIR}/conf/default.conf" \
    "${ROOTFS}/etc/nginx/conf.d/default.conf"

cp "${CONTAINER_DIR}/conf/90-container.cnf" \
    "${ROOTFS}/etc/mysql/mariadb.conf.d/90-container.cnf"

echo "Files copied into rootfs."
