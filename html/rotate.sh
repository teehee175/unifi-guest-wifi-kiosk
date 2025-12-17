#!/bin/bash
# html/rotate.sh

cd /var/www/html || exit 1

PHP_BIN="/usr/local/bin/php"

# Primary: env var
KEY="${ADMIN_KEY:-}"

# Legacy fallback: .env file (if present)
if [ -z "$KEY" ] && [ -f .env ]; then
    KEY=$(grep -E '^ADMIN_KEY=' .env | head -n1 | cut -d'=' -f2-)
fi

echo "[$(date -Iseconds)] Starting PSK rotation..." >> /var/log/rotate.log

if [ -z "$KEY" ]; then
    echo "[$(date -Iseconds)] ERROR: ADMIN_KEY not set (env or .env). Rotation aborted." >> /var/log/rotate.log
    exit 1
fi

RAW_JSON=$(ADMIN_KEY_CLI="$KEY" "$PHP_BIN" /var/www/html/changePSK.php 2>&1)
echo "[$(date -Iseconds)] changePSK.php output: $RAW_JSON" >> /var/log/rotate.log

STATUS=$(echo "$RAW_JSON" | "$PHP_BIN" -r '
$raw = stream_get_contents(STDIN);
$d = json_decode($raw, true);
if (is_array($d) && isset($d["status"])) {
    echo $d["status"];
}
')

if [ "$STATUS" != "success" ]; then
    echo "[$(date -Iseconds)] Rotation failed." >> /var/log/rotate.log
    exit 1
fi

echo "[$(date -Iseconds)] Rotation completed successfully." >> /var/log/rotate.log
chown www-data:www-data /var/log/rotate.log 2>/dev/null || true
echo "" >> /var/log/rotate.log

exit 0
