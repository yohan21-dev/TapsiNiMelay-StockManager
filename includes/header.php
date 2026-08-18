<?php
/**
 * Tapsi Stock — Shared Header
 *
 * Expected optional variables:
 * - $pageTitle
 * - $pageSubtitle
 * - $wide
 * - $activeNav
 * - $inAdmin
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$user = currentUser();

$activeNav = $activeNav ?? '';
$isAdminPage = isset($inAdmin);

$assetPath = $isAdminPage ? '../assets' : 'assets';
$homeHref  = $isAdminPage ? '../dashboard.php' : 'dashboard.php';

/**
 * Generate navigation URL depending on whether
 * the current page is inside /admin/.
 */
function navUrl(string $rootPath, string $adminPath, bool $isAdminPage): string
{
    return $isAdminPage ? $adminPath : $rootPath;
}

/**
 * Determine whether a navigation item is active.
 */
function navActive(string $nav, string $activeNav): string
{
    return $activeNav === $nav ? 'active' : '';
}

/**
 * Generate aria-current for active navigation items.
 */
function navCurrent(string $nav, string $activeNav): string
{
    return $activeNav === $nav ? ' aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
    >

    <meta name="theme-color" content="#ffffff">

    <title>
        <?= h($pageTitle ?? 'Tapsi Stock') ?> — Tapsi ni Melay
    </title>

    <link
        rel="stylesheet"
        href="<?= h($assetPath) ?>/css/style.css"
    >
</head>

<body>

<main class="app<?= !empty($wide) ? ' wide' : '' ?>">

    <!-- =========================================================
         HEADER
    ========================================================== -->
    <header class="header">

        <!-- Brand -->
        <a
            class="brand"
            href="<?= h($homeHref) ?>"
            aria-label="Tapsi Stock Dashboard"
        >

            <span class="logo-mark">
                <img
                    src="<?= h($assetPath) ?>/img/logo.jpg"
                    alt="Tapsi Ni Melay logo"
                    loading="eager"
                    onerror="
                        this.style.display='none';
                        this.nextElementSibling.style.display='flex';
                    "
                >

                <span
                    class="logo-placeholder"
                    aria-hidden="true"
                >
                    TS
                </span>
            </span>

            <span class="title-area">
                <span class="brand-name">
                    Tapsi ni Melay
                </span>

                <span class="brand-sub">
                    <?= h($pageSubtitle ?? 'Stock tracking for the business.') ?>
                </span>
            </span>

        </a>


        <!-- Header Actions -->
        <div class="header-actions">

            <!-- Current User -->
            <div
                class="user-chip"
                title="<?= h($user['full_name']) ?>"
            >
                <span class="user-name">
                    <?= h($user['full_name']) ?>
                </span>

                <span
                    class="pill <?= h($user['role']) ?>"
                    aria-label="Role: <?= h($user['role']) ?>"
                >
                    <?= h(ucfirst($user['role'])) ?>
                </span>
            </div>


            <!-- Theme Toggle -->
            <button
                type="button"
                class="icon-button"
                id="themeButton"
                title="Toggle dark mode"
                aria-label="Toggle dark mode"
                aria-pressed="false"
            >

                <!-- Sun -->
                <svg
                    class="icon-sun"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="4"></circle>

                    <path d="
                        M12 2v2
                        M12 20v2
                        M4.93 4.93l1.41 1.41
                        M17.66 17.66l1.41 1.41
                        M2 12h2
                        M20 12h2
                        M6.34 17.66l-1.41 1.41
                        M19.07 4.93l-1.41 1.41
                    "></path>
                </svg>

                <!-- Moon -->
                <svg
                    class="icon-moon"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="
                        M21 12.79
                        A9 9 0 1 1 11.21 3
                        A7 7 0 0 0 21 12.79z
                    "></path>
                </svg>

            </button>

            <!-- Hamburger / Nav Toggle (mobile) -->
            <button
                type="button"
                class="icon-button nav-toggle"
                id="navToggle"
                title="Menu"
                aria-label="Toggle navigation menu"
                aria-expanded="false"
                aria-controls="mainNav"
            >
                <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

        </div>

    </header>


    <!-- =========================================================
         NAVIGATION
    ========================================================== -->
    <nav
        class="nav"
        id="mainNav"
        aria-label="Main navigation"
    >
        <!-- Kitchen Count -->
        <a
            href="<?= h(navUrl('kitchen_count.php', '../kitchen_count.php', $isAdminPage)) ?>"
            class="<?= navActive('kitchen', $activeNav) ?>"
            <?= navCurrent('kitchen', $activeNav) ?>
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>

            <span>Kitchen Count</span>
        </a>
        <!-- Stock / Dashboard -->
        <a
            href="<?= h($homeHref) ?>"
            class="<?= navActive('dashboard', $activeNav) ?>"
            <?= navCurrent('dashboard', $activeNav) ?>
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="
                    M21 8
                    a2 2 0 0 0-1-1.73l-7-4
                    a2 2 0 0 0-2 0l-7 4
                    A2 2 0 0 0 3 8v8
                    a2 2 0 0 0 1 1.73l7 4
                    a2 2 0 0 0 2 0l7-4
                    A2 2 0 0 0 21 16z
                "></path>

                <path d="
                    M3.27 6.96
                    L12 12
                    l8.73-5.04
                    M12 22.08V12
                "></path>
            </svg>

            <span>Stock</span>
        </a>


        <!-- Reports -->
        <a
            href="<?= h(navUrl('reports.php', '../reports.php', $isAdminPage)) ?>"
            class="<?= navActive('reports', $activeNav) ?>"
            <?= navCurrent('reports', $activeNav) ?>
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M18 20V10"></path>
                <path d="M12 20V4"></path>
                <path d="M6 20v-6"></path>
            </svg>

            <span>Reports</span>
        </a>


        <?php if (isAdmin()): ?>

            <!-- Kitchen Items -->
            <a
                href="<?= h(navUrl('admin/kitchen_items.php', 'kitchen_items.php', $isAdminPage)) ?>"
                class="<?= navActive('kitchen-items', $activeNav) ?>"
                <?= navCurrent('kitchen-items', $activeNav) ?>
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M3 3h18v4H3z"></path>
                    <path d="M3 10h18"></path>
                    <path d="M3 14h18"></path>
                    <path d="M3 18h18"></path>
                </svg>

                <span>Kitchen Items</span>
            </a>

            <!-- Items -->
            <a
                href="<?= h(navUrl('admin/items.php', 'items.php', $isAdminPage)) ?>"
                class="<?= navActive('items', $activeNav) ?>"
                <?= navCurrent('items', $activeNav) ?>
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"
                    ></rect>

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"
                    ></rect>

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                        rx="1"
                    ></rect>

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                        rx="1"
                    ></rect>
                </svg>

                <span>Items</span>
            </a>


            <!-- Users -->
            <a
                href="<?= h(navUrl('admin/users.php', 'users.php', $isAdminPage)) ?>"
                class="<?= navActive('users', $activeNav) ?>"
                <?= navCurrent('users', $activeNav) ?>
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="
                        M16 21v-2
                        a4 4 0 0 0-4-4H6
                        a4 4 0 0 0-4 4v2
                    "></path>

                    <circle
                        cx="9"
                        cy="7"
                        r="4"
                    ></circle>

                    <path d="
                        M22 21v-2
                        a4 4 0 0 0-3-3.87
                        M16 3.13
                        a4 4 0 0 1 0 7.75
                    "></path>
                </svg>

                <span>Users</span>
            </a>

        <?php endif; ?>

        <!-- Shift -->
        <a
            href="<?= h(navUrl('shift.php', '../shift.php', $isAdminPage)) ?>"
            class="<?= navActive('shift', $activeNav) ?>"
            <?= navCurrent('shift', $activeNav) ?>
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
            </svg>

            <span>Shift</span>
        </a>
        <!-- Logout -->
        <a
            href="<?= h(navUrl('logout.php', '../logout.php', $isAdminPage)) ?>"
            class="nav-end"
        >
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="
                    M9 21H5
                    a2 2 0 0 1-2-2V5
                    a2 2 0 0 1 2-2h4
                "></path>

                <path d="
                    M16 17l5-5-5-5
                    M21 12H9
                "></path>
            </svg>

            <span>Logout</span>
        </a>

    </nav>