<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: items.php');
    exit;
}
csrfCheck();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
$unit = trim($_POST['unit'] ?? '') ?: 'pcs';
$currentStock = max(0, (int) filter_input(INPUT_POST, 'current_stock', FILTER_VALIDATE_INT));
$threshold = max(0, (int) filter_input(INPUT_POST, 'low_stock_threshold', FILTER_VALIDATE_INT));

if ($name === '') {
    header('Location: items.php');
    exit;
}

$db = getDB();

if ($id) {
    $stmt = $db->prepare('UPDATE items SET name=?, category_id=?, unit=?, current_stock=?, low_stock_threshold=? WHERE id=?');
    $stmt->execute([$name, $categoryId, $unit, $currentStock, $threshold, $id]);
} else {
    $stmt = $db->prepare('INSERT INTO items (name, category_id, unit, current_stock, low_stock_threshold) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $categoryId, $unit, $currentStock, $threshold]);
}

header('Location: items.php');
exit;
