#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
usage:
  sftp_clean.sh <container_id> <container_ip> <host_port> [ext_if] [bridge_if]

example:
  sftp_clean.sh test 10.200.1.3 10023
  sftp_clean.sh test 10.200.1.3 10023 ens3
EOF
  exit 1
}

if [ $# -lt 3 ] || [ $# -gt 5 ]; then
  usage
fi

CTR_ID="$1"
CTR_IP="$2"
HOST_PORT="$3"
EXT_IF="${4:-}"
BR_IF="${5:-ctrbr0}"

CTR_SSH_PORT=22

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "missing command: $1" >&2
    exit 1
  }
}

cexec_sh() {
  sudo ${HOME}/scratch-container exec "$CTR_ID" bash -lc "$1"
}

iptables_delete_if_exists() {
  local table="$1"
  shift

  if [ -n "$table" ]; then
    if sudo iptables -t "$table" -C "$@" 2>/dev/null; then
      sudo iptables -t "$table" -D "$@"
    else
      echo "not found: iptables -t $table $*" >&2
    fi
  else
    if sudo iptables -C "$@" 2>/dev/null; then
      sudo iptables -D "$@"
    else
      echo "not found: iptables $*" >&2
    fi
  fi
}

need_cmd sudo
need_cmd iptables

echo "[1/3] remove DNAT + FORWARD"

if [ -n "$EXT_IF" ]; then
  iptables_delete_if_exists nat PREROUTING \
    -i "$EXT_IF" -p tcp --dport "$HOST_PORT" \
    -j DNAT --to-destination "${CTR_IP}:${CTR_SSH_PORT}"

  iptables_delete_if_exists "" FORWARD \
    -i "$EXT_IF" -o "$BR_IF" -p tcp -d "$CTR_IP" --dport "$CTR_SSH_PORT" \
    -m conntrack --ctstate NEW,ESTABLISHED,RELATED -j ACCEPT

  iptables_delete_if_exists "" FORWARD \
    -i "$BR_IF" -o "$EXT_IF" -p tcp -s "$CTR_IP" --sport "$CTR_SSH_PORT" \
    -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
else
  iptables_delete_if_exists nat PREROUTING \
    -p tcp --dport "$HOST_PORT" \
    -j DNAT --to-destination "${CTR_IP}:${CTR_SSH_PORT}"

  iptables_delete_if_exists "" FORWARD \
    -o "$BR_IF" -p tcp -d "$CTR_IP" --dport "$CTR_SSH_PORT" \
    -m conntrack --ctstate NEW,ESTABLISHED,RELATED -j ACCEPT

  iptables_delete_if_exists "" FORWARD \
    -i "$BR_IF" -p tcp -s "$CTR_IP" --sport "$CTR_SSH_PORT" \
    -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
fi

echo "[2/3] stop sshd in container"
cexec_sh "pkill -x sshd >/dev/null 2>&1 || true"

echo "[3/3] done"