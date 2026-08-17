<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kitchen_items.php');
    exit;
}
csrfCheck();

$kitchenItemId = filter_input(INPUT_POST, 'kitchen_item_id', FILTER_VALIDATE_INT);
$stockItemId = filter_input(INPUT_POST, 'stock_item_id', FILTER_VALIDATE_INT);
$qty = filter_input(INPUT_POST, 'qty_per_order', FILTER_VALIDATE_INT);

if ($kitchenItemId && $stockItemId && $qty && $qty > 0) {
    $db = getDB();
    $db->prepare('INSERT INTO kitchen_count_recipe (kitchen_count_item_id, stock_item_id, qty_per_order)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE qty_per_order = VALUES(qty_per_order)')
       ->execute([$kitchenItemId, $stockItemId, $qty]);
}

header('Location: kitchen_items.php?edit=' . (int) $kitchenItemId);
exit;
