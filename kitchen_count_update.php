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

$shift = getOpenShift();
if (!$shift) {
    echo json_encode(['success' => false, 'error' => 'No active shift. Start a shift first.']);
    exit;
}

$kitchenItemId = filter_input(INPUT_POST, 'kitchen_item_id', FILTER_VALIDATE_INT);
$orderType = $_POST['order_type'] ?? '';
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

if (!$kitchenItemId || !in_array($orderType, ['dine_in', 'takeout'], true) || !in_array($amount, [1, -1], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid item, order type, or amount.']);
    exit;
}

$result = applyKitchenCount((int)$shift['id'], $kitchenItemId, $orderType, (int)currentUser()['id'], $amount);
echo json_encode($result);
