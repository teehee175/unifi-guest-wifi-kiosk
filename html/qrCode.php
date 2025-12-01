<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

$ssid = $_ENV['WIFI_SSID'] ?? 'GuestWiFi';
$psk = trim(file_get_contents(__DIR__.'/password.txt'));

header('Content-Type: image/png');

echo Builder::create()
    ->writer(new PngWriter())
    ->data("WIFI:T:WPA;S:$ssid;P:$psk;;")
    ->encoding(new Encoding('UTF-8'))
    ->size(300)
    ->margin(10)
    ->build()
    ->getString();