<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kitchen_items.php');
    exit;
}
csrfCheck();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $db = getDB();
    $db->prepare('UPDATE kitchen_count_items SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
}

header('Location: kitchen_items.php');
exit;
