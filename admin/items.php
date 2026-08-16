<?php
$inAdmin = true;
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();
$categories = getAllCategories();

$editItem = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch() ?: null;
}

$items = $db->query("SELECT i.*, c.name AS category_name
                      FROM items i LEFT JOIN categories c ON c.id = i.category_id
                      ORDER BY i.is_active DESC, c.name IS NULL, c.name, i.name")->fetchAll();

$pageTitle = 'Items';
$pageSubtitle = 'Add, edit, or retire the items you track stock for.';
$activeNav = 'items';
$wide = true;

include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <h2><?= $editItem ? 'Edit item' : 'Add a new item' ?></h2>
    <form method="post" action="item_save.php">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="field">
                <label for="name">Item name</label>
                <input type="text" id="name" name="name" maxlength="100" required
                       value="<?= h($editItem['name'] ?? '') ?>" placeholder="e.g. Aluminum Tray (Large)">
            </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— None —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (($editItem['category_id'] ?? null) == $cat['id']) ? 'selected' : '' ?>>
                            <?= h($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="unit">Unit</label>
                <input type="text" id="unit" name="unit" maxlength="20" value="<?= h($editItem['unit'] ?? 'pcs') ?>">
            </div>
            <div class="field">
                <label for="current_stock">Current stock</label>
                <input type="number" id="current_stock" name="current_stock" min="0" step="1"
                       value="<?= (int)($editItem['current_stock'] ?? 0) ?>">
            </div>
            <div class="field">
                <label for="low_stock_threshold">Low stock alert at</label>
                <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" step="1"
                       value="<?= (int)($editItem['low_stock_threshold'] ?? 5) ?>">
            </div>
        </div>

        <button type="submit" class="btn"><?= $editItem ? 'Save changes' : '+ Add item' ?></button>
        <?php if ($editItem): ?>
            <a href="items.php" class="btn secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h2>All items</h2>
    <?php if (empty($items)): ?>
        <p style="color:var(--muted)">No items yet — add one above.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Category</th><th>Unit</th><th>Stock</th>
                        <th>Low at</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= h($it['name']) ?></td>
                            <td><?= h($it['category_name'] ?? '—') ?></td>
                            <td><?= h($it['unit']) ?></td>
                            <td><?= (int)$it['current_stock'] ?></td>
                            <td><?= (int)$it['low_stock_threshold'] ?></td>
                            <td><span class="pill <?= $it['is_active'] ? 'active' : 'inactive' ?>"><?= $it['is_active'] ? 'Active' : 'Retired' ?></span></td>
                            <td style="white-space:nowrap;">
                                <a href="items.php?edit=<?= (int)$it['id'] ?>" class="btn small secondary">Edit</a>
                                <form method="post" action="item_toggle.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                    <button type="submit" class="btn small secondary"><?= $it['is_active'] ? 'Retire' : 'Restore' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
