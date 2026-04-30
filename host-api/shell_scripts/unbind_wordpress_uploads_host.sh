#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
  cat <<'EOF'
usage:
  unbind_wordpress_uploads_host.sh <container_id_or_rootfs> <sftp_user>

examples:
  unbind_wordpress_uploads_host.sh c_AAA12345 sftpuser
  unbind_wordpress_uploads_host.sh ${HOME}/rootfses/8/rootfs sftpuser

EOF
  exit 1
}

if [ "$#" -ne 2 ]; then
  usage
fi

TARGET="$1"
SFTP_USER="$2"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command on host: $1" >&2
    exit 1
  }
}

log() {
  printf '%s\n' "$*"
}

need_cmd sudo
need_cmd nsenter
need_cmd awk
need_cmd stat
need_cmd readlink
need_cmd find

FOUND_CONTAINER_ID=""
FOUND_PID=""

set_from_cgroup() {
  local cid="$1"
  local procs="/sys/fs/cgroup/${cid}/cgroup.procs"
  local p

  [ -f "$procs" ] || return 1

  for p in $(sudo awk 'NF { print }' "$procs" 2>/dev/null || true); do
    if [ -n "$p" ] && sudo test -d "/proc/$p"; then
      FOUND_CONTAINER_ID="$cid"
      FOUND_PID="$p"
      return 0
    fi
  done

  return 1
}

resolve_by_rootfs() {
  local rootfs="$1"
  local target_stat
  local target_real
  local procs
  local cgdir
  local cid
  local p
  local proc_stat
  local proc_real
  local mount_root

  [ -d "$rootfs" ] || return 1

  target_stat="$(sudo stat -Lc '%d:%i' "$rootfs" 2>/dev/null)" || return 1
  target_real="$(sudo readlink -f "$rootfs" 2>/dev/null || printf '%s\n' "$rootfs")"

  while IFS= read -r procs; do
    cgdir="${procs%/cgroup.procs}"
    cid="${cgdir##*/}"

    for p in $(sudo awk 'NF { print }' "$procs" 2>/dev/null || true); do
      [ -n "$p" ] || continue
      sudo test -d "/proc/$p" || continue

      proc_stat="$(sudo stat -Lc '%d:%i' "/proc/$p/root" 2>/dev/null || true)"
      if [ "$proc_stat" = "$target_stat" ]; then
        FOUND_CONTAINER_ID="$cid"
        FOUND_PID="$p"
        return 0
      fi

      proc_real="$(sudo readlink -f "/proc/$p/root" 2>/dev/null || true)"
      if [ "$proc_real" = "$target_real" ]; then
        FOUND_CONTAINER_ID="$cid"
        FOUND_PID="$p"
        return 0
      fi

      mount_root="$(sudo awk '$5 == "/" { print $4; exit }' "/proc/$p/mountinfo" 2>/dev/null || true)"
      if [ "$mount_root" = "$target_real" ]; then
        FOUND_CONTAINER_ID="$cid"
        FOUND_PID="$p"
        return 0
      fi
    done
  done < <(sudo find /sys/fs/cgroup -maxdepth 2 -type f -name cgroup.procs 2>/dev/null)

  return 1
}

resolve_target() {
  local target="$1"

  if [ -n "${SCRATCH_CONTAINER_ID:-}" ]; then
    if set_from_cgroup "$SCRATCH_CONTAINER_ID"; then
      return 0
    fi
  fi

  if [ -f "/sys/fs/cgroup/${target}/cgroup.procs" ]; then
    set_from_cgroup "$target"
    return $?
  fi

  if [ -d "$target" ]; then
    resolve_by_rootfs "$target"
    return $?
  fi

  return 1
}

log "[1/3] resolve running container"

if ! resolve_target "$TARGET"; then
  echo "failed to resolve running container from: $TARGET" >&2
  exit 1
fi

if [ -z "$FOUND_CONTAINER_ID" ] || [ -z "$FOUND_PID" ]; then
  echo "internal error: container resolved but id/pid is empty" >&2
  exit 1
fi

log "container id:  $FOUND_CONTAINER_ID"
log "container pid: $FOUND_PID"
log "target arg:    $TARGET"

log "[2/3] enter container mount namespace and unmount"

sudo nsenter \
  --target "$FOUND_PID" \
  --user \
  --mount \
  --root \
  --setuid 0 \
  --setgid 0 \
  /bin/bash -s -- "$SFTP_USER" <<'EOF'
set -Eeuo pipefail

SFTP_USER="$1"
DST="/srv/sftp/${SFTP_USER}/upload"

echo "[inside] destination: $DST"

if [ ! -d "$DST" ]; then
  echo "[inside] destination dir not found: $DST"
  exit 0
fi

if command -v findmnt >/dev/null 2>&1; then
  echo "[inside] before:"
  findmnt -T "$DST" -o TARGET,SOURCE,FSTYPE,OPTIONS || true
fi

if umount -R "$DST" 2>/dev/null; then
  echo "[inside] unmounted: $DST"
else
  echo "[inside] not mounted or already unmounted: $DST"
fi

if command -v findmnt >/dev/null 2>&1; then
  echo "[inside] after:"
  findmnt -T "$DST" -o TARGET,SOURCE,FSTYPE,OPTIONS || true
fi
EOF

log "[3/3] done"