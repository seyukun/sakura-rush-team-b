#!/usr/bin/env bash
set -Eeuo pipefail

PATH='/usr/sbin:/usr/bin:/sbin:/bin:/usr/local/sbin:/usr/local/bin'
umask 077

HOME_SAFE="${HOME:-/root}"
export HOME="${HOME_SAFE}"

id=''
user_id=''
ip=''
sftp_port=''

SCRATCH_CONTAINER="${SCRATCH_CONTAINER:-${HOME_SAFE}/scratch-container}"
SCRATCH_CONTAINER_CTL="${HOME}/containerctl.py"
USERS_DIR="${USERS_DIR:-${HOME_SAFE}/rootfses}"
CTR_BRIDGE="${CTR_BRIDGE:-ctrbr0}"

SFTP_CLEAN="${SFTP_CLEAN:-sftp_clean.sh}"
SFTP_EXT_IF="${SFTP_EXT_IF:-ens3}"

SFTP_USER="${SFTP_USER:-sftpuser}"

WORDPRESS_UNBIND_HOST="${WORDPRESS_UNBIND_HOST:-./shell_scripts/unbind_wordpress_uploads_host.sh}"

NGINX_CONF_DIR="${NGINX_CONF_DIR:-/etc/nginx/conf.d}"

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
  message_json=$(json_escape "container deleted")

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

trap 'on_error $? $LINENO' ERR

wait_container_stopped() {
  local retries="${1:-50}"
  local sleep_sec="${2:-0.2}"
  local i

  for ((i=1; i<=retries; i++)); do
    if sudo -n "${SCRATCH_CONTAINER}" exec "${id}" true >/dev/null 2>&1; then
      sleep "${sleep_sec}"
    else
      return 0
    fi
  done

  return 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --id) id="$2"; shift 2 ;;
    --user-id) user_id="$2"; shift 2 ;;
    --ip) ip="$2"; shift 2 ;;
    --sftp-port) sftp_port="$2"; shift 2 ;;
    *)
      fail_json 2 "\"UNKNOWN_ARG\"" "unknown arg: $1"
      ;;
  esac
done

stage='prepare_paths'
user_dir="${USERS_DIR}/${user_id}"
container_root="${user_dir}/rootfs"
ip_only="${ip%%/*}"

# 1. SFTP clean
stage='clean_sftp'
"${SFTP_CLEAN}" \
  "${id}" \
  "${ip_only}" \
  "${sftp_port}" \
  "${SFTP_EXT_IF}" \
  "${CTR_BRIDGE}" \
  1>&2

# 2. Remove main container process
stage='stop_container'
sudo -n "${SCRATCH_CONTAINER_CTL}" rm "${id}"

# 3. Wait until container becomes unavailable
stage='wait_container_stopped'
wait_container_stopped 50 0.2 || fail_json 1 "\"CONTAINER_STOP_TIMEOUT\"" "container did not stop"

# 4. Unbind WordPress uploads host-side
stage='unbind_wordpress_uploads'
if [[ -x "${WORDPRESS_UNBIND_HOST}" ]]; then
  "${WORDPRESS_UNBIND_HOST}" "${container_root}" "${SFTP_USER}" 1>&2 || true
fi

# 5. Remove nginx confs containing this IP
stage='remove_nginx_conf'
mapfile -t conf_files < <(sudo grep -lF -- "${ip_only}" "${NGINX_CONF_DIR}"/*.conf 2>/dev/null || true)

if [[ ${#conf_files[@]} -gt 0 ]]; then
  sudo rm -f "${conf_files[@]}" 1>&2

  stage='reload_nginx'
  sudo nginx -t 1>&2
  if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files nginx.service >/dev/null 2>&1; then
    sudo systemctl reload nginx 1>&2
  else
    sudo nginx -s reload 1>&2
  fi
fi

# 6. Remove rootfs
stage='remove_rootfs'
sudo rm -rf "${container_root}" 1>&2 || true
rmdir "${user_dir}" >/dev/null 2>&1 || true

stage='done'
ok_json