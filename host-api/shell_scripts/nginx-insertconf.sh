#!/usr/bin/env bash

#
# 使い方：bash nginx-insertconf.sh <domainname> <container-ip>
#
# 例：bash nginx-insertconf.sh example.kubernetes.jp 10.200.1.100
#

# 引数チェック
if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <domainname> <container-ip>"
  exit 1
fi

DOMAIN="$1"
CONTAINER_IP="$2"
OUTPUT_DIR="usercontent"
OUTPUT_FILE="/etc/nginx/${OUTPUT_DIR}/${DOMAIN}"

# 既存ファイルチェック
if [ -e "$OUTPUT_FILE" ]; then
  echo "エラー: 設定ファイル '${OUTPUT_FILE}' は既に存在します"
  exit 1
fi

# container-ip の形式チェック
if [[ ! "$CONTAINER_IP" =~ ^10\.200\.1\.([0-9]{1,3})$ ]]; then
  echo "エラー: container-ip は 10.200.1.2～10.200.1.254 の形式で指定してください"
  exit 1
fi

LAST_OCTET="${BASH_REMATCH[1]}"

# container-ip 範囲チェック
if (( LAST_OCTET < 2 || LAST_OCTET > 254 )); then
  echo "エラー: container-ip は 10.200.1.2～10.200.1.254 の範囲のみ許可されています"
  exit 1
fi

# Nginx 設定を書き出し

sudo tee "$OUTPUT_FILE" > /dev/null <<EOF
server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    location / {
        proxy_pass http://${CONTAINER_IP}:80;

        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
EOF

sudo nginx -t && sudo systemctl reload nginx
status=$?

if [ "$status" -ne 0 ]; then
  sudo rm -f "$OUTPUT_FILE"
fi

exit "$status"