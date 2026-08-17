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
$kitchenItemId = filter_input(INPUT_POST, 'kitchen_item_id', FILTER_VALIDATE_INT);

if ($id) {
    getDB()->prepare('DELETE FROM kitchen_count_recipe WHERE id = ?')->execute([$id]);
}

header('Location: kitchen_items.php?edit=' . (int) $kitchenItemId);
exit;
