#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
  cat <<'EOF'
usage:
  bind_wordpress_uploads_host.sh <container_id_or_rootfs> <sftp_user> [wordpress_uploads_abs] [web_user] [shared_group]

examples:
  bind_wordpress_uploads_host.sh c_AAA12345 sftpuser
  bind_wordpress_uploads_host.sh ${HOME}/rootfses/8/rootfs sftpuser
  bind_wordpress_uploads_host.sh ${HOME}/rootfses/8/rootfs sftpuser /var/www/html/wordpress/wp-content/uploads

EOF
  exit 1
}

if [ "$#" -lt 2 ] || [ "$#" -gt 5 ]; then
  usage
fi

TARGET="$1"
SFTP_USER="$2"
WORDPRESS_UPLOADS_ABS="${3:-/var/www/html/wordpress/wp-content/uploads}"
WEB_USER="${4:-www-data}"
SHARED_GROUP="${5:-uploads}"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command on host: $1" >&2
    exit 1
  }
}

log() {
  printf '%s\n' "$*"
}

case "$WORDPRESS_UPLOADS_ABS" in
  /*) ;;
  *)
    echo "wordpress uploads path must be absolute inside container: $WORDPRESS_UPLOADS_ABS" >&2
    exit 1
    ;;
esac

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

log "[1/5] resolve running container"

if ! resolve_target "$TARGET"; then
  echo "failed to resolve running container from: $TARGET" >&2
  echo "target must be either:" >&2
  echo "  - running container id, e.g. c_AAA12345" >&2
  echo "  - rootfs path of a currently running container" >&2
  exit 1
fi

if [ -z "$FOUND_CONTAINER_ID" ] || [ -z "$FOUND_PID" ]; then
  echo "internal error: container resolved but id/pid is empty" >&2
  exit 1
fi

log "container id:  $FOUND_CONTAINER_ID"
log "container pid: $FOUND_PID"
log "target arg:    $TARGET"

log "[2/5] enter container mount namespace"

sudo nsenter \
  --target "$FOUND_PID" \
  --user \
  --mount \
  --root \
  --setuid 0 \
  --setgid 0 \
  /bin/bash -s -- \
    "$SFTP_USER" \
    "$WORDPRESS_UPLOADS_ABS" \
    "$WEB_USER" \
    "$SHARED_GROUP" <<'EOF'
set -Eeuo pipefail

SFTP_USER="$1"
SRC="$2"
WEB_USER="$3"
SHARED_GROUP="$4"

CHROOT_DIR="/srv/sftp/${SFTP_USER}"
DST="${CHROOT_DIR}/upload"

need_inside_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command inside container: $1" >&2
    exit 1
  }
}

need_inside_cmd mount
need_inside_cmd umount
need_inside_cmd find
need_inside_cmd chown
need_inside_cmd chmod
need_inside_cmd groupadd
need_inside_cmd usermod
need_inside_cmd id
need_inside_cmd ls
need_inside_cmd rm
need_inside_cmd touch

echo "[inside] source:      $SRC"
echo "[inside] destination: $DST"
echo "[inside] sftp user:   $SFTP_USER"
echo "[inside] web user:    $WEB_USER"
echo "[inside] group:       $SHARED_GROUP"

if ! id "$SFTP_USER" >/dev/null 2>&1; then
  echo "sftp user not found inside container: $SFTP_USER" >&2
  exit 1
fi

if ! id "$WEB_USER" >/dev/null 2>&1; then
  echo "web user not found inside container: $WEB_USER" >&2
  exit 1
fi

if [ ! -d "$SRC" ]; then
  echo "wordpress uploads dir not found inside container: $SRC" >&2
  exit 1
fi

if [ ! -d "$CHROOT_DIR" ]; then
  echo "sftp chroot dir not found inside container: $CHROOT_DIR" >&2
  exit 1
fi

if [ ! -d "$DST" ]; then
  echo "sftp upload dir not found inside container: $DST" >&2
  exit 1
fi

echo "[inside] ensure chroot ownership"

chown root:root /srv /srv/sftp "$CHROOT_DIR"
chmod 755 /srv /srv/sftp "$CHROOT_DIR"

echo "[inside] prepare shared group"

groupadd -f "$SHARED_GROUP"
usermod -aG "$SHARED_GROUP" "$SFTP_USER"
usermod -aG "$SHARED_GROUP" "$WEB_USER"

echo "[inside] bind mount"

umount -R "$DST" 2>/dev/null || true

mount --bind "$SRC" "$DST"

if ! mount -o remount,bind,rw "$DST" 2>/dev/null; then
  mount -o remount,bind,rw "$SRC" "$DST"
fi

echo "[inside] fix uploads permissions"

chown -R "${WEB_USER}:${SHARED_GROUP}" "$SRC"
find "$SRC" -type d -exec chmod 2775 {} +
find "$SRC" -type f -exec chmod 0664 {} +

echo "[inside] verify bind mount by write-through"

PROBE=".bind-probe-$$"

rm -f "$SRC/$PROBE" "$DST/$PROBE"

touch "$DST/$PROBE"

if [ ! -e "$SRC/$PROBE" ]; then
  echo "bind verification failed: file created in destination is not visible in source" >&2
  rm -f "$DST/$PROBE"
  exit 1
fi

rm -f "$SRC/$PROBE" "$DST/$PROBE"

echo "[inside] verify sftp user write permission"

PROBE=".sftpuser-probe-$$"

rm -f "$SRC/$PROBE" "$DST/$PROBE"

if command -v su >/dev/null 2>&1; then
  su -s /bin/bash "$SFTP_USER" -c "touch '$DST/$PROBE'"
else
  echo "missing command inside container: su" >&2
  echo "bind mount succeeded, but sftp user write test was skipped" >&2
  touch "$DST/$PROBE"
fi

if [ ! -e "$SRC/$PROBE" ]; then
  echo "sftp user write verification failed: file created in destination is not visible in source" >&2
  rm -f "$DST/$PROBE"
  exit 1
fi

rm -f "$SRC/$PROBE" "$DST/$PROBE"

echo "[inside] verified"

if command -v findmnt >/dev/null 2>&1; then
  echo "[inside] findmnt:"
  findmnt -T "$DST" -o TARGET,SOURCE,FSTYPE,OPTIONS || true
fi

echo "[inside] final permissions:"
ls -ld "$CHROOT_DIR"
ls -ld "$DST"
ls -ld "$SRC"
id "$SFTP_USER"
id "$WEB_USER"
EOF

log "[3/5] done"
echo
echo "bind mounted inside running container:"
echo "  container:   $FOUND_CONTAINER_ID"
echo "  pid:         $FOUND_PID"
echo "  source:      $WORDPRESS_UPLOADS_ABS"
echo "  destination: /srv/sftp/${SFTP_USER}/upload"

log "[4/5] verify with scratch-container exec"

cat <<EOF

sudo ~/scratch-container exec ${FOUND_CONTAINER_ID} bash -lc '
  findmnt -T /srv/sftp/${SFTP_USER}/upload -o TARGET,SOURCE,FSTYPE,OPTIONS
  touch /srv/sftp/${SFTP_USER}/upload/.probe
  ls -l ${WORDPRESS_UPLOADS_ABS}/.probe
  rm -f ${WORDPRESS_UPLOADS_ABS}/.probe
'
EOF

log "[5/5] reconnect sftp session if already connected"