<?php
/**
 * Expects (optionally) $pageTitle, $pageSubtitle, $wide, $activeNav to be set
 * before including this file.
 */
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$activeNav = $activeNav ?? '';
$assetPath = isset($inAdmin) ? '../assets' : 'assets';
$homeHref = isset($inAdmin) ? '../dashboard.php' : 'dashboard.php';
$shiftHref = isset($inAdmin) ? '../shift.php' : 'shift.php';
$openShift = getOpenShift();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= h($pageTitle ?? 'Tapsi Stock') ?> — Tapsi Business</title>
    <link rel="stylesheet" href="<?= h($assetPath) ?>/css/style.css">
</head>
<body>
<main class="app<?= !empty($wide) ? ' wide' : '' ?>">

    <header class="header">
        <a class="brand" href="<?= h($homeHref) ?>">
            <span class="logo-mark">
                <img src="<?= h($assetPath) ?>/img/logo.jpg" alt="Tapsi Ni Melay logo"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span class="logo-placeholder" aria-hidden="true">TS</span>
            </span>
            <span class="title-area">
                <span class="brand-name">Tapsi Stock</span>
                <span class="brand-sub"><?= h($pageSubtitle ?? 'Stock tracking for the business.') ?></span>
            </span>
        </a>
        <div class="header-actions">
            <a class="shift-pill <?= $openShift ? 'on' : 'off' ?>" href="<?= h($shiftHref) ?>"
               title="<?= $openShift ? 'Shift open — tap to manage' : 'No active shift — tap to start one' ?>">
                <span class="shift-dot <?= $openShift ? 'on' : 'off' ?>"></span>
                <?= $openShift ? h(shiftDisplayLabel($openShift)) : 'No shift' ?>
            </a>
            <span class="user-chip">
                <?= h($user['full_name']) ?>
                <span class="pill <?= h($user['role']) ?>"><?= h($user['role']) ?></span>
            </span>
            <button class="icon-button" id="themeButton" title="Toggle dark mode" aria-label="Toggle dark mode">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
        </div>
    </header>

    <nav class="nav">
        <a href="<?= h($homeHref) ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"></path></svg>
            Stock
        </a>
        <a href="<?= isset($inAdmin) ? '../kitchen_count.php' : 'kitchen_count.php' ?>" class="<?= $activeNav === 'kitchen' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            Kitchen Count
        </a>
        <a href="<?= isset($inAdmin) ? '../reports.php' : 'reports.php' ?>" class="<?= $activeNav === 'reports' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
            Reports
        </a>
        <?php if (isAdmin()): ?>
            <a href="<?= isset($inAdmin) ? 'items.php' : 'admin/items.php' ?>" class="<?= $activeNav === 'items' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
                Items
            </a>
            <a href="<?= isset($inAdmin) ? 'kitchen_items.php' : 'admin/kitchen_items.php' ?>" class="<?= $activeNav === 'kitchen-items' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v4H3zM3 10h18M3 14h18M3 18h18"></path></svg>
                Kitchen Items
            </a>
            <a href="<?= isset($inAdmin) ? 'users.php' : 'admin/users.php' ?>" class="<?= $activeNav === 'users' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Users
            </a>
        <?php endif; ?>
        <a href="<?= h($shiftHref) ?>" class="<?= $activeNav === 'shift' ? 'active' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
            Shift
        </a>
        <a href="<?= isset($inAdmin) ? '../logout.php' : 'logout.php' ?>" class="nav-end">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5M21 12H9"></path></svg>
            Logout
        </a>
    </nav>
