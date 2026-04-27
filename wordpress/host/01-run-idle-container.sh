#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  01-run-idle-container.sh <rootfs> [container_id] [container_ip_cidr] [gateway] [bridge] [uid_base] [gid_base] [memory]

example:
  ./host/01-run-idle-container.sh ./rootfs wordpress 10.200.1.200/24 10.200.1.1 ctrbr0 50000 100000 512M
EOF
    exit 1
}

if [ $# -lt 1 ] || [ $# -gt 8 ]; then
    usage
fi

ROOTFS="$1"
CTR_ID="${2:-wordpress}"
CTR_IP_CIDR="${3:-10.200.1.200/24}"
GATEWAY="${4:-10.200.1.1}"
BRIDGE="${5:-ctrbr0}"
UID_BASE="${6:-50000}"
GID_BASE="${7:-100000}"
MEMORY="${8:-512M}"

SCRATCH="${SCRATCH:-${HOME}/scratch-container}"
DISTRO_NAME="${DISTRO_NAME:-debian}"

exec sudo "$SCRATCH" run "$ROOTFS" "$CTR_ID" "$DISTRO_NAME" \
    "$CTR_IP_CIDR" "$GATEWAY" "$BRIDGE" "$UID_BASE" "$GID_BASE" "$MEMORY" \
    bash -i
