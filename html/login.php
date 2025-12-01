<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
session_start();

$ADMIN_KEY = $_ENV['ADMIN_KEY'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['key'] ?? '') === $ADMIN_KEY) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid admin key.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>
<style>
body {
    background:#23272a;
    display:flex;justify-content:center;align-items:center;
    height:100vh;font-family:Arial;color:#fff;
}
.card {
    background:#2c2f33;
    padding:30px;border-radius:10px;width:300px;text-align:center;
}
input { width:100%;padding:10px;margin-top:10px;border-radius:6px;border:none; }
button { background:#7289da;color:white;border:none;padding:12px;margin-top:15px;width:100%;border-radius:6px;cursor:pointer; }
.error {color:#f04747;margin-top:10px;}
</style>
</head>
<body>
<div class="card">
    <h2>Admin Login</h2>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    <form method="post">
        <input type="password" name="key" placeholder="Admin Key">
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>