<?php
// html/changePSK.php
// Rotate the UniFi PSK and return JSON

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use UniFi_API\Client;

// Don't leak notices/warnings into JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

header('Content-Type: application/json');

// ----- Helpers -----

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

function env_or(string $name, $default = '')
{
    // Container env has priority, then .env
    $v = getenv($name);
    if ($v === false || $v === '') {
        $v = $_ENV[$name] ?? $default;
    }
    return $v;
}

function respond(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// ----- Auth check -----

$adminKey   = env_or('ADMIN_KEY', '');
$incoming   = $_GET['key'] ?? env_or('ADMIN_KEY_CLI', '');

if ($adminKey === '' || $incoming !== $adminKey) {
    respond(403, [
        'status'  => 'error',
        'message' => 'Unauthorized',
    ]);
}

// ----- Config -----

$ssid = env_or('WIFI_SSID', 'GuestWiFi');
$site = env_or('UNIFI_SITE', 'default');
$url  = rtrim(env_or('UNIFI_URL', ''), '/');
$user = env_or('UNIFI_USER', '');
$pass = env_or('UNIFI_PASS', '');

if ($url === '' || $user === '' || $pass === '') {
    respond(500, [
        'status'  => 'error',
        'message' => 'UNIFI_URL / UNIFI_USER / UNIFI_PASS must be set.',
    ]);
}

if (stripos($url, 'https://') !== 0) {
    respond(500, [
        'status'  => 'error',
        'message' => 'UNIFI_URL must start with https:// (include :8443 for legacy controllers).',
    ]);
}

// For UniFi OS (CloudKey Gen2 / UDM etc):
//   UNIFI_URL = https://192.168.35.10
// For legacy controller:
//   UNIFI_URL = https://192.168.35.10:8443

// ----- Generate new PSK -----

$chars   = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$newPsk  = substr(str_shuffle($chars), 0, 10);

try {
    // Build client
    $unifi = new Client(
        $user,
        $pass,
        $url,
        $site,
        '',     // version (auto)
        false   // ssl_verify = false (we use -k anyway)
    );

    // Swallow any library notices
    ob_start();
    $loginOk = $unifi->login();
    ob_end_clean();

    if (!$loginOk) {
        respond(500, [
            'status'  => 'error',
            'message' => 'UniFi login failed – check credentials, UNIFI_URL and controller firewall.',
        ]);
    }

    ob_start();
    $wlans = $unifi->list_wlanconf();
    ob_end_clean();

    if (!is_array($wlans)) {
        respond(500, [
            'status'  => 'error',
            'message' => 'Failed to fetch WLAN configuration from controller.',
        ]);
    }

    $matches = array_values(array_filter($wlans, function ($w) use ($ssid) {
        return (
            (isset($w->name) && $w->name === $ssid) ||
            (isset($w->ssid) && $w->ssid === $ssid)
        );
    }));

    if (empty($matches)) {
        respond(404, [
            'status'  => 'error',
            'message' => "SSID '{$ssid}' not found on controller.",
        ]);
    }

    $wlan = $matches[0];

    // Use convenience method to update passphrase
    $ok = $unifi->set_wlansettings($wlan->_id, $newPsk);

    if (!$ok) {
        respond(500, [
            'status'  => 'error',
            'message' => 'Controller did not accept updated WLAN settings.',
        ]);
    }

    file_put_contents(__DIR__ . '/password.txt', $newPsk);

    respond(200, [
        'status' => 'success',
        'ssid'   => $ssid,
        'psk'    => $newPsk,
    ]);
} catch (Throwable $e) {
    respond(500, [
        'status'  => 'error',
        'message' => 'Unexpected error: ' . $e->getMessage(),
    ]);
}
