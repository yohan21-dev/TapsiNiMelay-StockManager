<?php
$inAdmin = true;
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

$editUser = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare('SELECT id, username, full_name, role, is_active FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch() ?: null;
}

$users = $db->query('SELECT id, username, full_name, role, is_active, created_at FROM users ORDER BY created_at')->fetchAll();

$pageTitle = 'Users';
$pageSubtitle = 'Manage who can log in and what they can do.';
$activeNav = 'users';
$wide = true;

include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <h2><?= $editUser ? 'Edit user: ' . h($editUser['username']) : 'Add a new user' ?></h2>
    <form method="post" action="user_save.php">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" maxlength="50" required
                       value="<?= h($editUser['username'] ?? '') ?>" <?= $editUser ? 'readonly' : '' ?>>
            </div>
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100" required
                       value="<?= h($editUser['full_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="staff" <?= (($editUser['role'] ?? 'staff') === 'staff') ? 'selected' : '' ?>>Staff (stock + reports only)</option>
                    <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin (full access)</option>
                </select>
            </div>
            <div class="field">
                <label for="password"><?= $editUser ? 'New password (leave blank to keep current)' : 'Password' ?></label>
                <input type="password" id="password" name="password" minlength="6" autocomplete="new-password"
                       <?= $editUser ? '' : 'required' ?>>
            </div>
        </div>

        <button type="submit" class="btn"><?= $editUser ? 'Save changes' : '+ Add user' ?></button>
        <?php if ($editUser): ?>
            <a href="users.php" class="btn secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h2>All users</h2>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>Username</th><th>Full name</th><th>Role</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= h($u['username']) ?></td>
                        <td><?= h($u['full_name']) ?></td>
                        <td><span class="pill <?= $u['role'] ?>"><?= h($u['role']) ?></span></td>
                        <td><span class="pill <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                        <td style="white-space:nowrap;">
                            <a href="users.php?edit=<?= (int)$u['id'] ?>" class="btn small secondary">Edit</a>
                            <?php if ($u['id'] != currentUser()['id']): ?>
                                <form method="post" action="user_toggle.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="btn small secondary"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
