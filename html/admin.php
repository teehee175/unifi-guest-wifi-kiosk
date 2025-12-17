<?php
// html/admin.php
// Simple admin dashboard – no config-loader.php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use UniFi_API\Client;

// Hide notices/warnings in UI
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

// ----- Session / auth -----

session_start();

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

function env_or(string $name, $default = '')
{
    $v = getenv($name);
    if ($v === false || $v === '') {
        $v = $_ENV[$name] ?? $default;
    }
    return $v;
}

$adminKey = env_or('ADMIN_KEY', '');

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    // Require login via login.php
    header('Location: login.php');
    exit;
}

// ----- Config values -----

$ssid       = env_or('WIFI_SSID', 'GuestWiFi');
$logo       = env_or('LOGO_URL', 'logo.png');
$bgColor    = env_or('BACKGROUND_COLOR', '#23272a');
$cardColor  = env_or('CARD_COLOR', '#2c2f33');
$fontColor  = env_or('FONT_COLOR', '#ffffff');
$theme      = env_or('THEME', 'dark');
$cronExpr   = env_or('ROTATE_CRON', '0 3 * * *');

$unifiUrl   = env_or('UNIFI_URL', '');
$unifiSite  = env_or('UNIFI_SITE', 'default');
$unifiUser  = env_or('UNIFI_USER', '');
$unifiPass  = env_or('UNIFI_PASS', '');

// Current PSK (if exists)
$psk = file_exists(__DIR__ . '/password.txt')
    ? trim((string)file_get_contents(__DIR__ . '/password.txt'))
    : 'Not set';

// Messages
$rotateMessage   = '';
$rotateIsError   = false;
$unifiTestOutput = '';
$settingsSaved   = false;

// ----- .env helpers for theme values only -----

function load_env_file(): array
{
    $file = __DIR__ . '/.env';
    if (!file_exists($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env   = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $env[$k] = $v;
    }

    return $env;
}

function save_env_file(array $env): void
{
    ksort($env);
    $out = '';
    foreach ($env as $k => $v) {
        $out .= $k . '=' . $v . "\n";
    }
    file_put_contents(__DIR__ . '/.env', $out);
}

// ----- Handle POST actions -----

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rotate now
    if (isset($_POST['rotate'])) {
        $key = $adminKey;

        if ($key === '') {
            $rotateMessage = 'Rotation failed: ADMIN_KEY is not configured.';
            $rotateIsError = true;
        } else {
            $url = 'http://127.0.0.1/changePSK.php?key=' . urlencode($key);
            $cmd = 'curl -s -k ' . escapeshellarg($url);

            $raw = shell_exec($cmd);

            if ($raw === null) {
                $rotateMessage = 'Rotation failed: curl execution error inside container.';
                $rotateIsError = true;
            } else {
                $data = json_decode($raw, true);

                if (!is_array($data) || !isset($data['status'])) {
                    $rotateMessage = 'Rotation failed: invalid JSON response from changePSK.php.';
                    $rotateMessage .= ' Raw: ' . substr(trim($raw), 0, 200);
                    $rotateIsError = true;
                } elseif ($data['status'] !== 'success') {
                    $rotateMessage = 'Rotation failed: ' . ($data['message'] ?? 'unknown error');
                    $rotateIsError = true;
                } else {
                    $psk            = $data['psk'] ?? $psk;
                    $rotateMessage  = 'Password rotated successfully.';
                    $rotateIsError  = false;
                }
            }
        }
    }

    // Test UniFi connectivity
    if (isset($_POST['test_unifi'])) {
        try {
            $unifiTestOutput = '';

            if ($unifiUrl === '' || $unifiUser === '' || $unifiPass === '') {
                $unifiTestOutput = "❌ UNIFI_URL / UNIFI_USER / UNIFI_PASS not configured.";
            } else {
                $client = new Client(
                    $unifiUser,
                    $unifiPass,
                    rtrim($unifiUrl, '/'),
                    $unifiSite,
                    '',
                    false
                );

                ob_start();
                $ok     = $client->login();
                ob_end_clean();

                if (!$ok) {
                    $unifiTestOutput = "❌ Login failed – check credentials and controller URL.";
                } else {
                    $unifiTestOutput = "✅ Login OK to {$unifiUrl} (site: {$unifiSite})";
                }
            }
        } catch (Throwable $e) {
            $unifiTestOutput = "❌ Error: " . $e->getMessage();
        }
    }

    // Save UI settings to .env (non-secret only)
    if (isset($_POST['save_theme'])) {
        $env = load_env_file();

        $env['WIFI_SSID']        = $_POST['ssid']       ?? $ssid;
        $env['BACKGROUND_COLOR'] = $_POST['bgcolor']    ?? $bgColor;
        $env['CARD_COLOR']       = $_POST['cardcolor']  ?? $cardColor;
        $env['FONT_COLOR']       = $_POST['fontcolor']  ?? $fontColor;
        $env['LOGO_URL']         = $_POST['logo']       ?? $logo;
        $env['THEME']            = $_POST['theme']      ?? $theme;
        $env['ROTATE_CRON']      = $_POST['cron']       ?? $cronExpr;

        save_env_file($env);

        $settingsSaved = true;

        // Reload values from POST so UI reflects what was just saved
        $ssid      = $env['WIFI_SSID'];
        $bgColor   = $env['BACKGROUND_COLOR'];
        $cardColor = $env['CARD_COLOR'];
        $fontColor = $env['FONT_COLOR'];
        $logo      = $env['LOGO_URL'];
        $theme     = $env['THEME'];
        $cronExpr  = $env['ROTATE_CRON'];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #23272a;
            color: #ffffff;
            font-family: Arial, sans-serif;
            padding: 40px;
            margin: 0;
        }
        .container {
            background: #2c2f33;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            margin: auto;
        }
        h1, h2 { margin-top: 0; }
        pre {
            background: #1e2124;
            padding: 15px;
            border-radius: 5px;
            color: #ddd;
            white-space: pre-wrap;
            font-size: 13px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            margin-top: 6px;
            margin-bottom: 10px;
            border: none;
            background: #1e2124;
            color: #aaa;
            box-sizing: border-box;
        }
        input:disabled { opacity: 0.6; }
        button {
            padding: 8px 14px;
            border-radius: 6px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn { background: #7289da; color: white; }
        .logout { background: #f04747; color: white; }
        .section-title {
            margin-top: 30px;
            font-size: 1.3em;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        .badge-ok { background: #43b581; }
        .badge-warn { background: #f04747; }
        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .alert-ok { background: #2d7d46; }
        .alert-err { background: #a13232; }
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

    <?php if ($settingsSaved): ?>
        <div class="badge badge-ok">Settings saved</div>
    <?php endif; ?>

    <?php if ($rotateMessage !== ''): ?>
        <div class="alert <?= $rotateIsError ? 'alert-err' : 'alert-ok' ?>">
            <?= htmlspecialchars($rotateMessage) ?>
        </div>
    <?php endif; ?>

    <h2 class="section-title">🛠 Editable UI Settings (.env)</h2>
    <form method="post">
        <label>SSID (display only – container env overrides)</label>
        <input type="text" name="ssid" value="<?= htmlspecialchars($ssid) ?>">

        <label>BACKGROUND_COLOR</label>
        <input type="text" name="bgcolor" value="<?= htmlspecialchars($bgColor) ?>">

        <label>CARD_COLOR</label>
        <input type="text" name="cardcolor" value="<?= htmlspecialchars($cardColor) ?>">

        <label>FONT_COLOR</label>
        <input type="text" name="fontcolor" value="<?= htmlspecialchars($fontColor) ?>">

        <label>LOGO_URL</label>
        <input type="text" name="logo" value="<?= htmlspecialchars($logo) ?>">

        <label>THEME</label>
        <input type="text" name="theme" value="<?= htmlspecialchars($theme) ?>">

        <label>ROTATE_CRON (display only, actual CRON_SCHEDULE is controlled by container env)</label>
        <input type="text" name="cron" value="<?= htmlspecialchars($cronExpr) ?>">

        <button class="btn" name="save_theme" value="1">Save UI Settings</button>
    </form>

    <hr>

    <h2 class="section-title">🔒 Controller Settings (read-only)</h2>

    <label>UNIFI_URL</label>
    <input value="<?= htmlspecialchars($unifiUrl) ?>" disabled>

    <label>UNIFI_SITE</label>
    <input value="<?= htmlspecialchars($unifiSite) ?>" disabled>

    <label>UNIFI_USER</label>
    <input value="<?= htmlspecialchars($unifiUser) ?>" disabled>

    <label>UNIFI_PASS</label>
    <input value="<?= $unifiPass ? '********' : '' ?>" disabled>

    <hr>

    <h2 class="section-title">UniFi Status</h2>
    <form method="post">
        <button class="btn" name="test_unifi" value="1">Test Connection</button>
    </form>

    <?php if ($unifiTestOutput): ?>
        <pre><?= htmlspecialchars($unifiTestOutput) ?></pre>
    <?php endif; ?>

    <hr>

    <h2 class="section-title">Current Password</h2>
    <pre><?= htmlspecialchars($psk) ?></pre>

</div>
</body>
</html>
