<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use UniFi_API\Client as UniFiClient;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/*
 * AUTH ALLOWED:
 * - GET ?key=xxxx    (admin panel)
 * - ADMIN_KEY_CLI env (cron / CLI)
 */
$incomingKey = $_GET['key'] ?? getenv('ADMIN_KEY_CLI') ?? '';

if ($incomingKey !== ($_ENV['ADMIN_KEY'] ?? '')) {
    http_response_code(403);
    die(json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]));
}

$ssid = $_ENV['WIFI_SSID'] ?? 'GuestWiFi';
$site = $_ENV['UNIFI_SITE'] ?? 'default';
$url  = rtrim($_ENV['UNIFI_URL'] ?? '', '/');
$user = $_ENV['UNIFI_USER'] ?? '';
$pass = $_ENV['UNIFI_PASS'] ?? '';

$newPsk = substr(
    str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'),
    0,  
    10
);

try {
    $unifi = new UniFiClient($user, $pass, $url, $site, 'default', false);

    $login = $unifi->login();
    if (!$login) {
        throw new Exception('UniFi login failed');
    }

    $wlans = $unifi->list_wlanconf();
    $target = array_filter($wlans, fn($w) => $w->name === $ssid || $w->ssid === $ssid);

    if (!$target) {
        throw new Exception("SSID $ssid not found");
    }

    $wlan = array_values($target)[0];
    $wlan->x_passphrase = $newPsk;

    $unifi->set_wlansettings_base($wlan->_id, $wlan);

    file_put_contents(__DIR__ . '/password.txt', $newPsk);

    echo json_encode([
        'status'  => 'success',
        'ssid'    => $ssid,
        'psk'     => $newPsk,
        'updated' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}