#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
usage:
  sftp_init_start.sh <container_id> <container_ip> <host_port> <sftp_user> <sftp_pass> [ext_if] [bridge_if]

example:
  sftp_init_start.sh test 10.200.1.3 10023 sftpuser testpass
  sftp_init_start.sh test 10.200.1.3 10023 sftpuser testpass ens3
EOF
  exit 1
}

# -----------------------------
# arguments
# -----------------------------
if [ $# -lt 5 ] || [ $# -gt 7 ]; then
  usage
fi

CTR_ID="$1"
CTR_IP="$2"
HOST_PORT="$3"
SFTP_USER="$4"
SFTP_PASS="$5"
EXT_IF="${6:-}"
BR_IF="${7:-ctrbr0}"

# -----------------------------
# fixed parameters
# -----------------------------
CTR_SSH_PORT=22
SSHD_BIN=/usr/sbin/sshd
SSHD_CONF=/etc/ssh/sshd_config
SSHD_LOG=/tmp/sshd.log

SFTP_ROOT_BASE=/srv/sftp
SFTP_JAIL="${SFTP_ROOT_BASE}/${SFTP_USER}"
SFTP_UPLOAD="${SFTP_JAIL}/upload"

# -----------------------------
# utility functions
# -----------------------------
log() {
  printf '%s\n' "$*"
}

# ensure command exists
need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command: $1" >&2
    exit 1
  }
}

# execute command in container
cexec() {
  sudo ${HOME}/scratch-container exec "$CTR_ID" "$@"
}

# execute shell command in container
cexec_sh() {
  sudo ${HOME}/scratch-container exec "$CTR_ID" bash -lc "$1"
}

# add iptables rule if not exists
iptables_add_if_missing() {
  local table="$1"
  shift

  if [ -n "$table" ]; then
    if sudo iptables -t "$table" -C "$@" 2>/dev/null; then
      log "already exists: iptables -t $table $*"
    else
      sudo iptables -t "$table" -A "$@"
    fi
  else
    if sudo iptables -C "$@" 2>/dev/null; then
      log "already exists: iptables $*"
    else
      sudo iptables -A "$@"
    fi
  fi
}

# show reachable host address
host_hint() {
  if [ -n "$EXT_IF" ]; then
    local pub_ip
    pub_ip="$(ip -4 addr show "$EXT_IF" | awk '/inet /{print $2}' | cut -d/ -f1 | head -n1 || true)"
    if [ -n "$pub_ip" ]; then
      printf '%s\n' "$pub_ip"
      return 0
    fi
  fi
  printf '<host-ip-or-fqdn>\n'
}

# -----------------------------
# steps
# -----------------------------
preflight() {
  log "[0/5] preflight check"
  cexec true
}

prepare_container() {
  log "[1/5] setup user and chroot"

  # sshd runtime files
  cexec mkdir -p /run/sshd
  cexec_sh "ssh-keygen -A >/dev/null 2>&1 || true"

  # create user if missing
  if ! cexec id -u "$SFTP_USER" >/dev/null 2>&1; then
    cexec useradd -m -s /usr/sbin/nologin "$SFTP_USER"
  fi

  # always update password
  cexec_sh "echo '$SFTP_USER:$SFTP_PASS' | chpasswd"

  # directory layout
  cexec mkdir -p "$SFTP_UPLOAD"

  # chroot requires root-owned, non-writable parents
  cexec chown root:root /
  cexec chmod 755 /

  cexec chown root:root /srv
  cexec chmod 755 /srv

  cexec mkdir -p "$SFTP_ROOT_BASE"
  cexec chown root:root "$SFTP_ROOT_BASE"
  cexec chmod 755 "$SFTP_ROOT_BASE"

  cexec chown root:root "$SFTP_JAIL"
  cexec chmod 755 "$SFTP_JAIL"

  # writable directory for user
  cexec chown "$SFTP_USER:$SFTP_USER" "$SFTP_UPLOAD"
  cexec chmod 755 "$SFTP_UPLOAD"

  # set login directory after chroot
  local current_home
  current_home="$(cexec_sh "getent passwd '$SFTP_USER' | cut -d: -f6" || true)"
  if [ "$current_home" != "/upload" ]; then
    cexec usermod -d /upload "$SFTP_USER"
  fi
}

apply_sshd_config() {
  log "[2/5] configure sshd"

  # overwrite for SFTP-only container
  cexec_sh "cat > '$SSHD_CONF' <<EOF
Port ${CTR_SSH_PORT}

Protocol 2
PermitRootLogin no

PasswordAuthentication yes
PubkeyAuthentication yes

Subsystem sftp internal-sftp

Match User ${SFTP_USER}
    ChrootDirectory ${SFTP_JAIL}
    ForceCommand internal-sftp
    AllowTcpForwarding no
    X11Forwarding no
EOF"

  # config validation
  cexec "$SSHD_BIN" -t -f "$SSHD_CONF"
}

restart_sshd() {
  log "[3/5] restart sshd"

  # stop existing sshd
  cexec_sh "pkill -x sshd >/dev/null 2>&1 || true"
  sleep 1

#   # reset log
#   cexec_sh "rm -f '$SSHD_LOG'"

  # start sshd
  cexec_sh "setsid -f ${SSHD_BIN} -f '${SSHD_CONF}' -D -e </dev/null >>'${SSHD_LOG}' 2>&1"
  sleep 1

  # verify listening port
  if ! cexec_sh "ss -ltn | grep -q ':22'"; then
    echo "sshd is not listening on :22" >&2
    cexec_sh "pgrep -fa sshd || true" >&2 || true
    cexec_sh "ss -ltn || true" >&2 || true
    cexec cat "$SSHD_LOG" >&2 || true
    exit 1
  fi
}

apply_firewall() {
  log "[4/5] setup port forwarding"

  sudo sysctl -w net.ipv4.ip_forward=1 >/dev/null

  if [ -n "$EXT_IF" ]; then
    # DNAT from external interface
    iptables_add_if_missing nat PREROUTING \
      -i "$EXT_IF" -p tcp --dport "$HOST_PORT" \
      -j DNAT --to-destination "${CTR_IP}:${CTR_SSH_PORT}"

    # forward rules
    iptables_add_if_missing "" FORWARD \
      -i "$EXT_IF" -o "$BR_IF" -p tcp -d "$CTR_IP" --dport "$CTR_SSH_PORT" \
      -m conntrack --ctstate NEW,ESTABLISHED,RELATED -j ACCEPT

    iptables_add_if_missing "" FORWARD \
      -i "$BR_IF" -o "$EXT_IF" -p tcp -s "$CTR_IP" --sport "$CTR_SSH_PORT" \
      -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
  else
    # DNAT without interface restriction
    iptables_add_if_missing nat PREROUTING \
      -p tcp --dport "$HOST_PORT" \
      -j DNAT --to-destination "${CTR_IP}:${CTR_SSH_PORT}"

    iptables_add_if_missing "" FORWARD \
      -o "$BR_IF" -p tcp -d "$CTR_IP" --dport "$CTR_SSH_PORT" \
      -m conntrack --ctstate NEW,ESTABLISHED,RELATED -j ACCEPT

    iptables_add_if_missing "" FORWARD \
      -i "$BR_IF" -p tcp -s "$CTR_IP" --sport "$CTR_SSH_PORT" \
      -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
  fi
}

print_summary() {
  log "[5/5] done"
  echo
  echo "SFTP endpoint:"
  echo "  Host: $(host_hint)"
  echo "  Port: ${HOST_PORT}"
  echo "  User: ${SFTP_USER}"
  echo "  Example: sftp -P ${HOST_PORT} ${SFTP_USER}@$(host_hint)"
  echo
  echo "Container:"
  echo "  id: ${CTR_ID}"
  echo "  private: ${CTR_IP}:${CTR_SSH_PORT}"
  echo "  ssh log: ${SSHD_LOG}"
  echo "  jail: ${SFTP_JAIL}"
  echo "  upload: ${SFTP_UPLOAD}"
}

# -----------------------------
# main
# -----------------------------
need_cmd sudo
need_cmd iptables
need_cmd awk
need_cmd grep
need_cmd ip

preflight
prepare_container
apply_sshd_config
restart_sshd
apply_firewall
print_summary