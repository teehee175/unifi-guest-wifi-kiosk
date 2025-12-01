<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use UniFi_API\Client as UniFiClient;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
session_start();

if (!($_SESSION['admin'] ?? false)) {
    header("Location: login.php");
    exit;
}

$ADMIN_KEY = $_ENV['ADMIN_KEY'];

// ENV values
$ssid      = $_ENV['WIFI_SSID']         ?? '';
$logo      = $_ENV['LOGO_URL']          ?? '';
$bgColor   = $_ENV['BACKGROUND_COLOR']  ?? '#ffffff';
$cardColor = $_ENV['CARD_COLOR']        ?? '#ffffff';
$fontColor = $_ENV['FONT_COLOR']        ?? '#000000';
$theme     = $_ENV['THEME']             ?? 'dark';

$unifiUrl  = $_ENV['UNIFI_URL'];
$unifiSite = $_ENV['UNIFI_SITE'];
$unifiUser = $_ENV['UNIFI_USER'];
$unifiPass = $_ENV['UNIFI_PASS'] ?? '';

$psk = file_exists("password.txt") ? trim(file_get_contents("password.txt")) : "Not set";

// UniFi status check
function unifiStatusSimple() {
    try {
        $c = new UniFiClient($_ENV['UNIFI_USER'], $_ENV['UNIFI_PASS'], $_ENV['UNIFI_URL'], $_ENV['UNIFI_SITE']);
        return $c->login() ? 200 : 403;
    } catch (Exception $e) {
        return 500;
    }
}

$log = file_exists('/var/log/rotate.log')
    ? shell_exec("tail -n 50 /var/log/rotate.log")
    : "No logs available.";

$rotate_output = "";
$unifi_test_output = "";

/* ROTATE NOW – WITH REDIRECT SO REFRESH DOESN'T REPEAT */
if (isset($_POST['rotate'])) {
    $rotate_output = shell_exec("curl -s -k 'http://localhost/changePSK.php?key=$ADMIN_KEY' 2>&1");
    header("Location: admin.php?rotated=1");
    exit;
}

/* TEST UNIFI CONNECTION */
if (isset($_POST['test_unifi'])) {
    try {
        $c = new UniFiClient($_ENV['UNIFI_USER'], $_ENV['UNIFI_PASS'], $_ENV['UNIFI_URL'], $_ENV['UNIFI_SITE']);
        $login = $c->login();

        if (!$login) {
            $unifi_test_output = "❌ Login failed";
        } else {
            $sites = $c->list_sites();
            $unifi_test_output = "✅ Login OK\n\nSites:\n" . print_r($sites, true);
        }

    } catch (Exception $e) {
        $unifi_test_output = "❌ Error: " . $e->getMessage();
    }
}

/* SAVE ENV */
if (isset($_POST['save_all'])) {

    $env = file_get_contents(".env");

    $fields = [
        'WIFI_SSID'        => $_POST['ssid'],
        'BACKGROUND_COLOR' => $_POST['bgcolor'],
        'CARD_COLOR'       => $_POST['cardcolor'],
        'FONT_COLOR'       => $_POST['fontcolor'],
        'LOGO_URL'         => $_POST['logo'],
        'THEME'            => $_POST['theme'],
        'UNIFI_URL'        => $_POST['unifi_url'],
        'UNIFI_SITE'       => $_POST['unifi_site'],
        'UNIFI_USER'       => $_POST['unifi_user'],
        'ROTATE_CRON'      => '"' . $_POST['cron'] . '"',
    ];

    foreach ($fields as $k => $v) {
        $env = preg_replace("/^$k=.*/m", "$k=$v", $env);
    }

    if (!empty($_POST['unifi_pass'])) {
        $env = preg_replace("/^UNIFI_PASS=.*/m", "UNIFI_PASS=" . $_POST['unifi_pass'], $env);
    }

    file_put_contents(".env", $env);

    file_put_contents("/etc/cron.d/rotate-psk",
        $_POST['cron'] . " root /var/www/html/rotate.sh >> /var/log/cron.log 2>&1\n"
    );

    header("Location: admin.php?saved=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body { 
    background:#23272a;
    color: <?= $theme === "dark" ? "white" : "black" ?>;
    font-family:Arial;
    padding:40px;
}

.container { 
    background:#2c2f33;
    padding:30px;
    border-radius:10px;
    max-width:900px;
    margin:auto;
}

pre {
    background:#1e2124;
    padding:15px;
    border-radius:5px;
    color:#ddd;
    white-space:pre-wrap;
}

input, select {
    width:100%;
    padding:10px;
    border-radius:6px;
    margin-top:10px;
    border:none;
}

button {
    padding:10px;
    border-radius:6px;
    margin-top:10px;
    border:none;
    cursor:pointer;
}

.btn { background:#7289da;color:white; }
.logout { background:#f04747;color:white; }
.status-ok { color:#43b581; }
.status-bad { color:#f04747; }
</style>

</head>
<body>

<div class="container">

<h1>Admin Dashboard – <?= htmlspecialchars($ssid) ?></h1>

<form method="post" action="logout.php" style="display:inline;">
    <button class="logout">Logout</button>
</form>

<form method="post" style="display:inline;">
    <button class="btn" name="rotate" value="1">Rotate Now</button>
</form>

<h2>Current Password</h2>
<div style="font-size:1.5em;font-weight:bold;"><?= htmlspecialchars($psk) ?></div>
<img src="qrCode.php?ts=<?= time() ?>" width="200">

<?php if ($rotate_output): ?>
<h2>Rotation Output</h2>
<pre><?= htmlspecialchars($rotate_output) ?></pre>
<?php endif; ?>

<hr>

<h2>All Settings</h2>
<form method="post">

    <label>SSID</label>
    <input name="ssid" value="<?= $ssid ?>">

    <label>Rotate Time (Cron)</label>
    <input name="cron" value="<?= trim($_ENV['ROTATE_CRON'], '"') ?>">

    <label>Background Colour</label>
    <input type="color" name="bgcolor" value="<?= $bgColor ?>">

    <label>Card Colour</label>
    <input type="color" name="cardcolor" value="<?= $cardColor ?>">

    <label>Font Colour (Index Only)</label>
    <input type="color" name="fontcolor" value="<?= $fontColor ?>">

    <label>Logo URL</label>
    <input name="logo" value="<?= $logo ?>">

    <label>Theme</label>
    <select name="theme">
        <option value="dark"  <?= $theme==='dark'?'selected':'' ?>>Dark</option>
        <option value="light" <?= $theme==='light'?'selected':'' ?>>Light</option>
    </select>

    <label>UniFi URL</label>
    <input name="unifi_url" value="<?= $unifiUrl ?>">

    <label>UniFi Site</label>
    <input name="unifi_site" value="<?= $unifiSite ?>">

    <label>UniFi Username</label>
    <input name="unifi_user" value="<?= $unifiUser ?>">

    <label>UniFi Password (leave blank to keep current)</label>
    <input type="password" name="unifi_pass">

    <button class="btn" name="save_all">Save All</button>

</form>

<hr>

<h2>UniFi Controller Status</h2>
<p>
<?php $stat = unifiStatusSimple(); ?>
<?= $stat == 200 ? "<span class='status-ok'>🟢 Online</span>" : "<span class='status-bad'>🔴 Offline ($stat)</span>" ?>
</p>

<form method="post">
    <button class="btn" name="test_unifi">Test UniFi Connection</button>
</form>

<?php if ($unifi_test_output): ?>
<h2>UniFi Test Output</h2>
<pre><?= htmlspecialchars($unifi_test_output) ?></pre>
<?php endif; ?>

<h2>Rotation Log</h2>
<pre><?= htmlspecialchars($log) ?></pre>

<h2>System Information</h2>
<pre><?=
"PHP Version: ".PHP_VERSION."\n".
"Uptime: ".trim(shell_exec("uptime -p"))."\n".
"Container: ".trim(shell_exec("hostname"))."\n";
?></pre>

</div>
</body>
</html>