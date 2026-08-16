<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}
csrfCheck();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Safety: never let an admin disable their own account
if ($id && $id != currentUser()['id']) {
    $db = getDB();
    $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
}

header('Location: users.php');
exit;
