<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Session expired. Please refresh the page.']);
    exit;
}

$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

if (!$itemId || !in_array($amount, [1, -1], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid item or amount.']);
    exit;
}

$result = applyStockChange($itemId, (int)currentUser()['id'], $amount);

if (!$result['success']) {
    echo json_encode($result);
    exit;
}

$db = getDB();
$stmt = $db->prepare('SELECT low_stock_threshold FROM items WHERE id = ?');
$stmt->execute([$itemId]);
$threshold = (int)($stmt->fetchColumn() ?: 0);

echo json_encode([
    'success'   => true,
    'new_stock' => $result['new_stock'],
    'threshold' => $threshold,
]);
