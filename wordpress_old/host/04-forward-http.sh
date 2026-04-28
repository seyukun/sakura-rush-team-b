#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
usage:
  04-forward-http.sh <name> <container_ip> <host_port> [container_port] [server_name]

examples:
  # One container per host port
  ./host/04-forward-http.sh wp1 10.200.1.200 8081
  ./host/04-forward-http.sh wp2 10.200.1.201 8082

  # Multiple containers on the same host port, separated by Host header
  ./host/04-forward-http.sh wp1 10.200.1.200 8080 80 wp1.local
  ./host/04-forward-http.sh wp2 10.200.1.201 8080 80 wp2.local

description:
  Create or update a host-side nginx reverse proxy config.

  This script does not use iptables.
  It does not modify WordPress home/siteurl.

  It creates:
      /etc/nginx/conf.d/<name>.conf

  It exposes:
      http://<host>:<host_port>/ -> http://<container_ip>:<container_port>/

multiple containers:
  - Different host ports: use different <name> and different <host_port>.
  - Same host port: use different <name> and different [server_name].
  - The same host:port cannot route to multiple containers without server_name.
EOF
    exit 1
}

if [ $# -lt 3 ] || [ $# -gt 5 ]; then
    usage
fi

NAME="$1"
CTR_IP="$2"
HOST_PORT="$3"
CTR_PORT="${4:-80}"
SERVER_NAME="${5:-_}"

CONF_PATH="/etc/nginx/conf.d/${NAME}.conf"

need_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "missing command: $1" >&2
        exit 1
    }
}

validate_name() {
    local value="$1"

    if [[ "$value" == */* ]] || [[ "$value" == .* ]] || [[ "$value" =~ [^A-Za-z0-9_.-] ]]; then
        echo "Error: name must contain only A-Za-z0-9_.- and must not contain slash: ${value}" >&2
        exit 1
    fi
}

validate_port() {
    local name="$1"
    local value="$2"

    if ! [[ "$value" =~ ^[0-9]+$ ]] || [ "$value" -lt 1 ] || [ "$value" -gt 65535 ]; then
        echo "Error: ${name} must be a TCP port number: ${value}" >&2
        exit 1
    fi
}

need_cmd sudo
need_cmd nginx
need_cmd curl
need_cmd awk
need_cmd grep

validate_name "$NAME"
validate_port HOST_PORT "$HOST_PORT"
validate_port CTR_PORT "$CTR_PORT"

echo "[1/5] backend check: http://${CTR_IP}:${CTR_PORT}/"
curl -4fsSI --max-time 5 "http://${CTR_IP}:${CTR_PORT}/" >/dev/null

# If server_name is "_", this config is intended as the default server for this port.
# Only one default_server can exist for each address:port. Multiple containers on the
# same host port must use explicit server_name values.
LISTEN_SUFFIX=""
if [ "$SERVER_NAME" = "_" ]; then
    LISTEN_SUFFIX=" default_server"

    DUPLICATES="$(sudo nginx -T 2>/dev/null \
        | awk -v port="$HOST_PORT" '
            /# configuration file / { file=$0 }
            $0 ~ "listen .*" port && $0 ~ "default_server" {
                print file "\n" $0
            }
        ' \
        | grep -v "# configuration file ${CONF_PATH}:" || true)"

    if [ -n "$DUPLICATES" ]; then
        echo "Error: another nginx default_server already exists for port ${HOST_PORT}." >&2
        echo "Use a different host_port, or pass a server_name as the 5th argument." >&2
        echo >&2
        echo "$DUPLICATES" >&2
        exit 1
    fi
fi

echo "[2/5] write nginx config: ${CONF_PATH}"

if sudo test -e "$CONF_PATH"; then
    TS="$(date +%Y%m%d-%H%M%S)"
    sudo cp -a "$CONF_PATH" "${CONF_PATH}.bak.${TS}"
fi

sudo tee "$CONF_PATH" >/dev/null <<EOF_NGINX
server {
    listen ${HOST_PORT}${LISTEN_SUFFIX};
    listen [::]:${HOST_PORT}${LISTEN_SUFFIX};

    server_name ${SERVER_NAME};

    access_log /var/log/nginx/${NAME}.access.log;
    error_log  /var/log/nginx/${NAME}.error.log warn;

    client_max_body_size 64m;

    location / {
        proxy_pass http://${CTR_IP}:${CTR_PORT};

        proxy_http_version 1.1;

        # Keep WordPress' internal canonical host stable.
        proxy_set_header Host ${CTR_IP};

        # Preserve public request metadata for logs and future app-layer use.
        proxy_set_header X-Forwarded-Host \$http_host;
        proxy_set_header X-Forwarded-Port \$server_port;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;

        # Required so nginx can rewrite response bodies.
        proxy_set_header Accept-Encoding "";

        # Rewrite redirect responses from the backend to the public host:port.
        proxy_redirect http://${CTR_IP}/ \$scheme://\$http_host/;
        proxy_redirect http://${CTR_IP}:${CTR_PORT}/ \$scheme://\$http_host/;
        proxy_redirect http://127.0.0.1/ \$scheme://\$http_host/;
        proxy_redirect http://localhost/ \$scheme://\$http_host/;

        # Rewrite absolute URLs embedded in WordPress HTML/CSS/JS/JSON.
        sub_filter_once off;
        sub_filter_types text/html text/css application/javascript application/json application/xml text/xml;
        sub_filter 'http://${CTR_IP}' '\$scheme://\$http_host';
        sub_filter 'http://${CTR_IP}:${CTR_PORT}' '\$scheme://\$http_host';
        sub_filter 'http://127.0.0.1' '\$scheme://\$http_host';
        sub_filter 'http://localhost' '\$scheme://\$http_host';
    }
}
EOF_NGINX

echo "[3/5] validate nginx"
if ! sudo nginx -t; then
    echo "Error: nginx config validation failed." >&2
    echo "Check duplicate listen/server blocks:" >&2
    echo "  sudo nginx -T 2>/dev/null | grep -nB3 -A12 'listen .*${HOST_PORT}'" >&2
    exit 1
fi

echo "[4/5] reload nginx"
if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files nginx.service >/dev/null 2>&1; then
    sudo systemctl reload nginx
else
    sudo nginx -s reload
fi

echo "[5/5] done"
echo
echo "Host-side nginx reverse proxy configured:"
echo "  name:    ${NAME}"
echo "  public:  http://<host>:${HOST_PORT}/"
echo "  backend: http://${CTR_IP}:${CTR_PORT}/"
echo "  server:  ${SERVER_NAME}"
echo "  config:  ${CONF_PATH}"
echo "  logs:    /var/log/nginx/${NAME}.access.log"
echo
echo "Test:"
if [ "$SERVER_NAME" = "_" ]; then
    echo "  curl -4I http://127.0.0.1:${HOST_PORT}/"
else
    echo "  curl -4I -H 'Host: ${SERVER_NAME}' http://127.0.0.1:${HOST_PORT}/"
fi
