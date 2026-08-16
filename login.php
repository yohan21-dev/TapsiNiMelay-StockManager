<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (attemptLogin($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Login — Tapsi Stock</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <h1>🍽️ Tapsi Stock</h1>
        <p class="sub">Sign in to manage your business stock</p>

        <?php if ($error): ?>
            <div class="error-box"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn" style="width:100%;">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
