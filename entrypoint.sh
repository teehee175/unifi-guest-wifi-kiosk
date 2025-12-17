#!/bin/bash
set -e
# Determine cron schedule
SCHEDULE="${ROTATE_CRON:-${CRON_SCHEDULE:-0 3 * * *}}"

echo "[entrypoint] Using cron schedule: $SCHEDULE"

# Write cron job
echo "$SCHEDULE root /var/www/html/rotate.sh >> /var/log/cron.log 2>&1" \
    > /etc/cron.d/rotate-psk

chmod 0644 /etc/cron.d/rotate-psk
crontab /etc/cron.d/rotate-psk

# Start cron
cron

# Start Apache
echo "[entrypoint] Starting Apache..."
exec apache2-foreground
