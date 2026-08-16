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
$username = trim($_POST['username'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$role = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
$password = $_POST['password'] ?? '';

if ($username === '' || $fullName === '') {
    header('Location: users.php');
    exit;
}

$db = getDB();

if ($id) {
    // Editing: username stays fixed, password only updates if a new one was entered
    if ($password !== '') {
        if (strlen($password) < 6) {
            header('Location: users.php?edit=' . $id . '&error=short_password');
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET full_name=?, role=?, password_hash=? WHERE id=?');
        $stmt->execute([$fullName, $role, $hash, $id]);
    } else {
        $stmt = $db->prepare('UPDATE users SET full_name=?, role=? WHERE id=?');
        $stmt->execute([$fullName, $role, $id]);
    }
} else {
    if (strlen($password) < 6) {
        header('Location: users.php?error=short_password');
        exit;
    }

    // Prevent duplicate usernames
    $check = $db->prepare('SELECT id FROM users WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) {
        header('Location: users.php?error=duplicate');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $hash, $fullName, $role]);
}

header('Location: users.php');
exit;
