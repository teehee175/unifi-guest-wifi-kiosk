<?php
// ===========================
// index.php
// ===========================
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Container env (protected)
$ssid = getenv('WIFI_SSID') ?: 'GuestWiFi';

// UI config from .env (safe values only)
$logo      = $_ENV['LOGO_URL']         ?? 'logo.png';
$bgColor   = $_ENV['BACKGROUND_COLOR'] ?? '#ffffff';
$cardColor = $_ENV['CARD_COLOR']       ?? '#ffffff';
$fontColor = $_ENV['FONT_COLOR']       ?? '#000000';

$psk = file_exists(__DIR__ . "/password.txt")
    ? trim(file_get_contents(__DIR__ . "/password.txt"))
    : "Not set";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($ssid) ?> Guest Wi-Fi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
    background: <?= htmlspecialchars($bgColor) ?>;
    font-family: Arial, sans-serif;
    color: <?= htmlspecialchars($fontColor) ?>;
}

.wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
}

.card {
    background: <?= htmlspecialchars($cardColor) ?>;
    padding: 80px 120px;
    border-radius: 30px;
    text-align: center;
    min-width: 700px;
    max-width: 900px;
    box-shadow: 0 0 55px rgba(0,0,0,0.45);
}

.logo {
    width: 700px;
    max-width: 90%;
    margin-bottom: 50px;
}

.ssid {
    font-size: 2.8em;
    font-weight: 700;
    margin-bottom: 10px;
}

.password {
    font-size: 3.2em;
    font-weight: bold;
    margin: 25px 0 40px 0;
}

.qr {
    width: 480px;
    height: 480px;
}
</style>

<script>
let lastData = {
    psk: "<?= htmlspecialchars($psk) ?>",
    ssid: "<?= htmlspecialchars($ssid) ?>",
    logo: "<?= htmlspecialchars($logo) ?>",
    bg: "<?= htmlspecialchars($bgColor) ?>",
    card: "<?= htmlspecialchars($cardColor) ?>",
    font: "<?= htmlspecialchars($fontColor) ?>"
};

function checkUpdates() {
    fetch("status.php?ts=" + Date.now())
        .then(r => r.json())
        .then(data => {
            if (data.psk !== lastData.psk) {
                lastData.psk = data.psk;
                document.getElementById("psk").innerText = data.psk;
                document.getElementById("qr").src = "qrCode.php?ts=" + Date.now();
            }
            if (data.logo !== lastData.logo) {
                lastData.logo = data.logo;
                document.getElementById("logo").src = data.logo;
            }
            if (data.bg !== lastData.bg) {
                lastData.bg = data.bg;
                document.body.style.background = data.bg;
            }
            if (data.card !== lastData.card) {
                lastData.card = data.card;
                document.getElementById("card").style.background = data.card;
            }
            if (data.font !== lastData.font) {
                lastData.font = data.font;
                document.body.style.color = data.font;
            }
        })
        .catch(console.error);
}

setInterval(checkUpdates, 5000);
</script>

</head>
<body>

<div class="wrapper">
    <div class="card" id="card">
        <img class="logo" id="logo" src="<?= htmlspecialchars($logo) ?>" alt="Logo">
        <div class="ssid" id="ssid"><?= htmlspecialchars($ssid) ?></div>
        <div id="psk" class="password"><?= htmlspecialchars($psk) ?></div>
        <img id="qr" class="qr" src="qrCode.php?ts=<?= time() ?>" alt="QR Code">
    </div>
</div>

</body>
</html>
