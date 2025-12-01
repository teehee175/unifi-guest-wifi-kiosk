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
    background: <?= $bgColor ?>;
    font-family: Arial, sans-serif;
    color: <?= $fontColor ?>;
    transition: background 0.3s;
}

.wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
}

.card {
    background: <?= $cardColor ?>;
    padding: 80px 120px;
    border-radius: 30px;
    text-align: center;
    min-width: 700px;
    max-width: 900px;
    box-shadow: 0 0 55px rgba(0,0,0,0.45);
    transition: background 0.3s;
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
    bg: "<?= $bgColor ?>",
    card: "<?= $cardColor ?>",
    font: "<?= $fontColor ?>"
};

function checkUpdates() {
    fetch("status.php?ts=" + Date.now())
        .then(r => r.json())
        .then(data => {
            // PASSWORD
            if (data.psk !== lastData.psk) {
                lastData.psk = data.psk;
                document.getElementById("psk").innerText = data.psk;
                document.getElementById("qr").src = "qrCode.php?ts=" + Date.now();
            }

            // SSID
            if (data.ssid !== lastData.ssid) {
                lastData.ssid = data.ssid;
                document.getElementById("ssid").innerText = data.ssid;
            }

            // LOGO
            if (data.logo !== lastData.logo) {
                lastData.logo = data.logo;
                document.getElementById("logo").src = data.logo;
            }

            // BACKGROUND
            if (data.bg !== lastData.bg) {
                lastData.bg = data.bg;
                document.body.style.background = data.bg;
            }

            // CARD COLOUR
            if (data.card !== lastData.card) {
                lastData.card = data.card;
                document.getElementById("card").style.background = data.card;
            }

            // FONT COLOUR
            if (data.font !== lastData.font) {
                lastData.font = data.font;
                document.body.style.color = data.font;
            }
        })
        .catch(e => console.error(e));
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

        <img id="qr" class="qr" src="qrCode.php?ts=<?= time() ?>">
    </div>
</div>

<!-- Uncomment this if you want admin button -->
<!--
<a href="admin.php" style="
    position:fixed;bottom:20px;right:20px;
    background:#7289da;padding:12px 18px;
    color:white;border-radius:8px;text-decoration:none;
">Admin</a>
-->

</body>
</html>
