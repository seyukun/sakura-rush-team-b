#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
  cat <<'EOF'
usage:
  unbind_wordpress_uploads_host.sh <container_root> <sftp_user>

example:
  unbind_wordpress_uploads_host.sh /home/k-hamada/rootfses/1/rootfs sftpuser
EOF
  exit 1
}

if [ $# -ne 2 ]; then
  usage
fi

CONTAINER_ROOT="${1%/}"
SFTP_USER="$2"
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
need_cmd umount

log "[1/2] check mountpoint"
if sudo mountpoint -q "$DST"; then
  log "mounted, unmounting: $DST"
  sudo umount "$DST"
else
  log "not mounted: $DST"
fi

log "[2/2] done"