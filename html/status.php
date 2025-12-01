<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$ssid       = $_ENV['WIFI_SSID']        ?? 'GuestWiFi';
$logo       = $_ENV['LOGO_URL']         ?? 'logo.png';
$bgColor    = $_ENV['BACKGROUND_COLOR'] ?? '#ffffff';
$cardColor  = $_ENV['CARD_COLOR']       ?? '#ffffff';
$fontColor  = $_ENV['FONT_COLOR']       ?? '#000000';

$psk = file_exists("password.txt") ? trim(file_get_contents("password.txt")) : "Not set";

echo json_encode([
    "psk"  => $psk,
    "ssid" => $ssid,
    "logo" => $logo,
    "bg"   => $bgColor,
    "card" => $cardColor,
    "font" => $fontColor
]);