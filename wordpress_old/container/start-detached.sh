#!/usr/bin/env bash
set -Eeuo pipefail

SERVICE_NAME="${1:?service name is required}"
shift

if [ "$#" -eq 0 ]; then
    echo "usage: start-detached.sh <service-name> <command> [args...]" >&2
    exit 1
fi

case "$SERVICE_NAME" in
    *[!A-Za-z0-9_.-]*|'')
        echo "Error: invalid service name: ${SERVICE_NAME}" >&2
        exit 1
        ;;
esac

LOG_FILE="/tmp/${SERVICE_NAME}.log"
PID_FILE="/run/${SERVICE_NAME}.pid"

rm -f "$LOG_FILE" "$PID_FILE"

setsid -f bash -c '
    set -Eeuo pipefail
    pid_file="$1"
    shift
    echo "$$" > "$pid_file"
    exec "$@"
' _ "$PID_FILE" "$@" </dev/null >>"$LOG_FILE" 2>&1

for _ in $(seq 1 20); do
    if [ -s "$PID_FILE" ]; then
        PID="$(cat "$PID_FILE")"
        if kill -0 "$PID" >/dev/null 2>&1; then
            echo "${SERVICE_NAME} started: pid=${PID}, log=${LOG_FILE}"
            exit 0
        fi
    fi
    sleep 0.1
done

echo "Error: ${SERVICE_NAME} did not start." >&2
cat "$LOG_FILE" >&2 || true
exit 1
