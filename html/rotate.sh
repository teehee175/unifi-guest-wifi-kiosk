#!/bin/bash
cd /var/www/html

KEY=$(grep ADMIN_KEY .env | cut -d'=' -f2)

echo "[$(date)] Starting PSK rotation..." >> /var/log/rotate.log

ADMIN_KEY_CLI="$KEY" php /var/www/html/changePSK.php >> /var/log/rotate.log 2>&1

echo "[$(date)] Rotation completed." >> /var/log/rotate.log