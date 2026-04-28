#!/usr/bin/env bash
set -Eeuo pipefail

PATH='/usr/sbin:/usr/bin:/sbin:/bin:/usr/local/sbin:/usr/local/bin'
umask 077

id=''
user_id=''
ip=''
cpu_quota_ms=''
cpu_period_ms=''
mem_m=''
volume_size=''
sftp_port=''

SCRATCH_CONTAINER="${SCRATCH_CONTAINER:-${HOME}/scratch-container}"
SCRATCH_CONTAINER_CTL="${HOME}/containerctl.py"
BASE_ROOTFS="${BASE_ROOTFS:-${HOME}/base_rootfs}"
USERS_DIR="${USERS_DIR:-${HOME}/rootfses}"
CTR_GATEWAY="${CTR_GATEWAY:-10.200.1.1}"
CTR_BRIDGE="${CTR_BRIDGE:-ctrbr0}"

SFTP_INIT="${SFTP_INIT:-sftp_init_start.sh}"
SFTP_USER="${SFTP_USER:-sftpuser}"
SFTP_EXT_IF="${SFTP_EXT_IF:-ens3}"

stage='init'
container_root=''

json_escape() {
  local s="${1-}"
  s=${s//\\/\\\\}
  s=${s//\"/\\\"}
  s=${s//$'\n'/\\n}
  s=${s//$'\r'/\\r}
  s=${s//$'\t'/\\t}
  s=${s//$'\f'/\\f}
  s=${s//$'\b'/\\b}
  printf '"%s"' "$s"
}

fail_json() {
  local exit_code="$1"
  local error_code="$2"
  local message="$3"

  local id_json user_id_json ip_json stage_json msg_json container_root_json
  id_json=$(json_escape "${id:-}")
  user_id_json=$(json_escape "${user_id:-}")
  ip_json=$(json_escape "${ip:-}")
  stage_json=$(json_escape "${stage:-}")
  msg_json=$(json_escape "${message:-}")
  container_root_json=$(json_escape "${container_root:-}")

  cat <<EOF
{
  "ok": false,
  "error_code": ${error_code},
  "message": ${msg_json},
  "stage": ${stage_json},
  "exit_code": ${exit_code},
  "id": ${id_json},
  "user_id": ${user_id_json},
  "ip": ${ip_json},
  "container_root": ${container_root_json}
}
EOF
  exit "${exit_code}"
}

ok_json() {
  local id_json user_id_json container_name_json container_root_json message_json
  id_json=$(json_escape "${id}")
  user_id_json=$(json_escape "${user_id}")
  container_name_json=$(json_escape "${id}")
  container_root_json=$(json_escape "${container_root}")
  message_json=$(json_escape "container created")

  cat <<EOF
{
  "ok": true,
  "id": ${id_json},
  "user_id": ${user_id_json},
  "container_name": ${container_name_json},
  "container_root": ${container_root_json},
  "message": ${message_json}
}
EOF
}

on_error() {
  local exit_code="$1"
  local line_no="$2"
  fail_json "${exit_code}" "\"SCRIPT_FAILED\"" "failed at stage=${stage}, line=${line_no}"
}

wait_container_ready() {
  local retries="${1:-50}"
  local sleep_sec="${2:-0.2}"
  local i

  for ((i=1; i<=retries; i++)); do
    if sudo -n "${SCRATCH_CONTAINER}" exec "${id}" true >/dev/null 2>&1; then
      return 0
    fi
    sleep "${sleep_sec}"
  done

  return 1
}


trap 'on_error $? $LINENO' ERR

while [[ $# -gt 0 ]]; do
  case "$1" in
    --id) id="$2"; shift 2 ;;
    --user-id) user_id="$2"; shift 2 ;;
    --ip) ip="$2"; shift 2 ;;
    --cpu-quota-ms) cpu_quota_ms="$2"; shift 2 ;;
    --cpu-period-ms) cpu_period_ms="$2"; shift 2 ;;
    --mem-m) mem_m="$2"; shift 2 ;;
    --volume-size) volume_size="$2"; shift 2 ;;
    --sftp-port) sftp_port="$2"; shift 2 ;;
    *)
      fail_json 2 "\"UNKNOWN_ARG\"" "unknown arg: $1"
      ;;
  esac
done

# stdin から SFTP パスワードを 1 行受け取る
stage='read_sftp_password'
IFS= read -r sftp_password || true

# パス準備
stage='prepare_paths'
user_dir="${USERS_DIR}/${user_id}"
container_root="${user_dir}/rootfs"

mkdir -p "${user_dir}" 1>&2

# rootfs のコピー
stage='copy_rootfs'
sudo cp -r "${BASE_ROOTFS}" "${container_root}" 1>&2
sudo chown -R $USER:$USER ${container_root}

# volume_size
# TODO: volume 作成処理

# コンテナ起動
stage='run_container'
sudo "${SCRATCH_CONTAINER_CTL}" run \
  "${container_root}" \
  "${id}" \
  "debian" \
  "${ip}" \
  "${CTR_GATEWAY}" \
  "${CTR_BRIDGE}" \
  "${cpu_quota_ms}" \
  "${cpu_period_ms}" \
  "${mem_m}M" \
  nginx -g 'daemon off;'

stage='wait_container_ready'
wait_container_ready 50 0.2

# SFTP 初期化
# sftp_init_start.sh の仕様:
#   sftp_init_start.sh <container_id> <container_ip> <host_port> <sftp_user> <sftp_pass> [ext_if] [bridge_if]
ip_only="${ip%/*}"
stage='init_sftp'
"${SFTP_INIT}" \
  "${id}" \
  "${ip_only}" \
  "${sftp_port}" \
  "${SFTP_USER}" \
  "${sftp_password}" \
  "${SFTP_EXT_IF}" \
  "${CTR_BRIDGE}" \
  1>&2

stage='done'
ok_json