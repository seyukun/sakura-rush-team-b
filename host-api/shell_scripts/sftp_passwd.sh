#!/usr/bin/env bash
set -euo pipefail

usage() {
  echo "usage:"
  echo "  sftp_passwd.sh <container_id> <sftp_user> <new_password>"
  echo
  echo "example:"
  echo "  sftp_passwd.sh test sftpuser NewSecretPass123"
  exit 1
}

# -----------------------------
# args
# -----------------------------
if [ $# -ne 3 ]; then
  usage
fi

CTR_ID="$1"
SFTP_USER="$2"
NEW_PASS="$3"

# -----------------------------
# helpers
# -----------------------------
cexec() {
  sudo ${HOME}/scratch-container exec "$CTR_ID" "$@"
}

cexec_sh() {
  sudo ${HOME}/scratch-container exec "$CTR_ID" bash -lc "$1"
}

# -----------------------------
# main
# -----------------------------
# ユーザー存在確認
if ! cexec id -u "$SFTP_USER" >/dev/null 2>&1; then
  echo "user not found: $SFTP_USER" >&2
  exit 1
fi

# パスワード変更
cexec_sh "echo '$SFTP_USER:$NEW_PASS' | chpasswd"

echo "password updated:"
echo "  container: $CTR_ID"
echo "  user:      $SFTP_USER"