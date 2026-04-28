#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
  cat <<'EOF'
usage:
  bind_wordpress_uploads_host.sh <container_root> <sftp_user> [wordpress_uploads_rel]

example:
  bind_wordpress_uploads_host.sh /home/k-hamada/rootfses/1/rootfs sftpuser
  bind_wordpress_uploads_host.sh /home/k-hamada/rootfses/1/rootfs sftpuser /var/www/html/wordpress/wp-content/uploads
EOF
  exit 1
}

if [ $# -lt 2 ] || [ $# -gt 3 ]; then
  usage
fi

CONTAINER_ROOT="${1%/}"
SFTP_USER="$2"
WORDPRESS_UPLOADS_REL="${3:-/var/www/html/wordpress/wp-content/uploads}"

SRC="${CONTAINER_ROOT}${WORDPRESS_UPLOADS_REL}"
DST="${CONTAINER_ROOT}/srv/sftp/${SFTP_USER}/upload"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command: $1" >&2
    exit 1
  }
}

log() {
  printf '%s\n' "$*"
}

need_cmd sudo
need_cmd mountpoint
need_cmd mount

log "[1/5] preflight"
if [ ! -d "$CONTAINER_ROOT" ]; then
  echo "container_root not found: $CONTAINER_ROOT" >&2
  exit 1
fi

if [ ! -d "$SRC" ]; then
  echo "wordpress uploads dir not found: $SRC" >&2
  exit 1
fi

if [ ! -d "$DST" ]; then
  echo "sftp upload dir not found: $DST" >&2
  exit 1
fi

log "[2/5] ensure destination is not already mounted"
if sudo mountpoint -q "$DST"; then
  log "already mounted, unmounting first: $DST"
  sudo umount "$DST"
fi

log "[3/5] bind mount"
sudo mount --bind "$SRC" "$DST"

log "[4/5] verify"
sudo mountpoint -q "$DST"

log "[5/5] done"
echo
echo "bind mounted:"
echo "  source:      $SRC"
echo "  destination: $DST"