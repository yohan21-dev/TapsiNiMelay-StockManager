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
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    header('Location: kitchen_items.php');
    exit;
}

$db = getDB();

if ($id) {
    $db->prepare('UPDATE kitchen_count_items SET name = ? WHERE id = ?')->execute([$name, $id]);
    header('Location: kitchen_items.php?edit=' . $id);
} else {
    $db->prepare('INSERT INTO kitchen_count_items (name) VALUES (?)')->execute([$name]);
    header('Location: kitchen_items.php');
}
exit;
