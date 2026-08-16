<?php
/**
 * Expects (optionally) $pageTitle, $pageSubtitle, $wide, $activeNav to be set
 * before including this file.
 */
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= h($pageTitle ?? 'Tapsi Stock') ?> — Tapsi Business</title>
    <link rel="stylesheet" href="<?= isset($inAdmin) ? '../assets/css/style.css' : 'assets/css/style.css' ?>">
</head>
<body>
<main class="app<?= !empty($wide) ? ' wide' : '' ?>">

    <header class="header">
        <div class="title-area">
            <h1>🍽️ Tapsi Stock</h1>
            <p><?= h($pageSubtitle ?? 'Stock tracking for the business.') ?></p>
        </div>
        <div class="header-actions">
            <span style="font-size:13px; color:var(--muted);">
                <?= h($user['full_name']) ?> · <span class="pill <?= $user['role'] ?>"><?= h($user['role']) ?></span>
            </span>
            <button class="icon-button" id="themeButton" title="Toggle dark mode">🌙</button>
        </div>
    </header>

    <nav class="nav">
        <a href="<?= isset($inAdmin) ? '../dashboard.php' : 'dashboard.php' ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">📦 Stock</a>
        <a href="<?= isset($inAdmin) ? '../reports.php' : 'reports.php' ?>" class="<?= $activeNav === 'reports' ? 'active' : '' ?>">📊 Reports</a>
        <?php if (isAdmin()): ?>
            <a href="<?= isset($inAdmin) ? 'items.php' : 'admin/items.php' ?>" class="<?= $activeNav === 'items' ? 'active' : '' ?>">🗂️ Items</a>
            <a href="<?= isset($inAdmin) ? 'users.php' : 'admin/users.php' ?>" class="<?= $activeNav === 'users' ? 'active' : '' ?>">👤 Users</a>
        <?php endif; ?>
        <a href="<?= isset($inAdmin) ? '../logout.php' : 'logout.php' ?>">🚪 Logout</a>
    </nav>
