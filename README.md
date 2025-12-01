# UniFi Guest Wi-Fi PSK Rotator + QR Kiosk Page

This project rotates a UniFi Guest Wi-Fi PSK, updates a branded kiosk webpage with the new password and QR code, and provides an admin page for manual updates. It supports both Docker and Linux/Cron installations.

---

## Environment Variables (.env)

    WIFI_SSID=guest-wifi
    UNIFI_URL=https://192.168.1.10:8443
    UNIFI_SITE=default
    UNIFI_USER=psk-rotate
    UNIFI_PASS=safepassword
    ADMIN_KEY=supersecret123
    BACKGROUND_URL=https://your-wallpaper.jpg
    LOGO_URL=https://your-logo.png
    THEME=light
    BG_COLOR=#000000
    INNER_COLOR=#ffffff
    TEXT_COLOR=#ffffff

---

## Docker Deployment

### Build the image:
    docker build -t psk-rotate .
### Run the container:
    docker run -d -p 8080:80 --restart unless-stopped --env-file .env --name psk-rotate psk-rotate
### Access the kiosk:
    http://<ip>:8080/
### Access the admin page:
    http://<ip>:8080/admin?key=YOUR_ADMIN_KEY

---

## Linux Deployment (No Docker)

### 1. Install Dependencies
    apt install -y php php-curl php-cli composer qrencode wget unzip

### 2. Download the Project
    cd /var/www/
    wget https://github.com/teehee175/unifi-guest-wifi-kiosk/archive/refs/heads/main.zip -O kiosk.zip
    unzip kiosk.zip
    rm kiosk.zip
    mv unifi-guest-wifi-kiosk-main html

### 3. Install PHP Dependencies
    cd /var/www/html
    composer install

### 4. Make the rotate script executable
    chmod +x rotate.sh

---

## Systemd Service & Timer (Recommended)

### 1. Create the systemd service  
Create `/etc/systemd/system/unifi-psk-rotate.service`:

    [Unit]
    Description=Unifi Guest WiFi PSK Rotation Script
    Wants=network-online.target
    After=network-online.target

    [Service]
    Type=oneshot
    WorkingDirectory=/var/www/html
    ExecStart=/var/www/html/rotate.sh
    User=www-data
    Group=www-data
    Nice=10

### 2. Create the systemd timer  
Create `/etc/systemd/system/unifi-psk-rotate.timer`:

    [Unit]
    Description=Run Unifi PSK Rotation Daily

    [Timer]
    OnCalendar=*-*-* 03:00:00
    Persistent=true

    [Install]
    WantedBy=timers.target

### 3. Enable and start the timer
    systemctl daemon-reload
    systemctl enable --now unifi-psk-rotate.timer

### Check next scheduled run
    systemctl list-timers --all | grep unifi

### Run the rotation manually
    systemctl start unifi-psk-rotate.service
    journalctl -u unifi-psk-rotate.service -f

---

## File Structure

/var/www/html/

    index.php
    admin.php
    changepass.php
    rotate.sh
    .env
    qrcode.png
    assets/

---

## Kiosk Features

- Auto-refreshes when the PSK updates
- Background image support
- Logo scaling
- Inner box colour options
- Light/dark theme support
- Optimised for kiosk displays

---

## How It Works

1. Script logs into the UniFi Controller API
2. Generates a new random PSK
3. Updates the configured Wi-Fi network
4. Generates a new QR code image
5. Updates the kiosk webpage
6. Kiosk instantly displays the new Wi-Fi password and QR code

---
