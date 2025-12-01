<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$auth_user = 'admun';
$auth_pass = 'numda';

// Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $auth_user || $_SERVER['PHP_AUTH_PW'] !== $auth_pass) {
    header('WWW-Authenticate: Basic realm="PSK Rotate Admin"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Unauthorized');
}

$envFile = __DIR__ . '/.env';

// Load current .env values
$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $env['UNIFI_USER'] = $_POST['controlleruser'];
    $env['UNIFI_PASS'] = $_POST['controllerpassword'];
    $env['UNIFI_URL']  = $_POST['controllerurl'];
    $env['UNIFI_SITE'] = $_POST['site_id'];
    $env['WIFI_SSID']  = $_POST['wlan_id'];

    // Rewrite .env safely
    $newEnv = "";
    foreach ($env as $k => $v) {
        $newEnv .= "$k=$v\n";
    }
    file_put_contents($envFile, $newEnv);

    $message = "Configuration updated successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
body {
  background-color: #5a69bf;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
  color: white;
  font-family: Arial, sans-serif;
}
form {
  background: #2f3b58;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  width: 320px;
}
h2 { text-align: center; margin-bottom: 20px; }
input[type="text"], input[type="password"] {
  width: 100%;
  padding: 6px;
  margin-bottom: 15px;
  border: none;
  border-radius: 4px;
}
input[type="submit"] {
  width: 100%;
  background-color: #3e4f88;
  color: white;
  padding: 8px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
input[type="submit"]:hover {
  background-color: #4f62a8;
}
.message {
  text-align: center;
  background-color: #4169e1;
  padding: 5px;
  border-radius: 5px;
}
</style>
</head>
<body>

<form method="post">
  <h2>UniFi PSK Rotate Config</h2>
  <?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  Controller User:<br>
  <input type="text" name="controlleruser" value="<?= htmlspecialchars($env['UNIFI_USER'] ?? '') ?>"><br>
  Controller Password:<br>
  <input type="password" name="controllerpassword"><br>
  Controller URL:<br>
  <input type="text" name="controllerurl" value="<?= htmlspecialchars($env['UNIFI_URL'] ?? '') ?>"><br>
  Site ID:<br>
  <input type="text" name="site_id" value="<?= htmlspecialchars($env['UNIFI_SITE'] ?? 'default') ?>"><br>
  WLAN (SSID):<br>
  <input type="text" name="wlan_id" value="<?= htmlspecialchars($env['WIFI_SSID'] ?? 'GuestWiFi') ?>"><br>
  <input type="submit" value="Save">
  <p style="text-align:center"><a href="/qrCode.php" style="color:#9fbaff;">View QR Page</a></p>
</form>

</body>
</html>