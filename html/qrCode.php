<?php
// ===========================
// qrCode.php
// ===========================
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: image/png');

$ssid = getenv('WIFI_SSID') ?: 'GuestWiFi';
$psk  = file_exists(__DIR__ . '/password.txt')
    ? trim(file_get_contents(__DIR__ . '/password.txt'))
    : '';

echo Builder::create()
    ->writer(new PngWriter())
    ->data("WIFI:T:WPA;S:$ssid;P:$psk;;")
    ->size(300)
    ->margin(10)
    ->build()
    ->getString();
