<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shift.php');
    exit;
}
csrfCheck();

$label = trim($_POST['label'] ?? '');
startShift((int) currentUser()['id'], $label);

header('Location: shift.php');
exit;
