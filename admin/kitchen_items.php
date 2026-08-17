<?php
$inAdmin = true;
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();
$stockOptions = getActiveStockItemsForSelect();

$editItem = null;
$recipe = [];
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM kitchen_count_items WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editItem = $stmt->fetch() ?: null;
    if ($editItem) {
        $recipe = getKitchenRecipe((int) $editItem['id']);
    }
}

$items = getAllKitchenItems();

$pageTitle = 'Kitchen Items';
$pageSubtitle = 'Manage the dishes staff tally in Kitchen Count, and optionally link stock they use.';
$activeNav = 'kitchen-items';
$wide = true;

include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <h2><?= $editItem ? 'Edit kitchen item' : 'Add a new kitchen item' ?></h2>
    <form method="post" action="kitchen_item_save.php">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int) $editItem['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="field">
                <label for="name">Item name</label>
                <input type="text" id="name" name="name" maxlength="100" required
                       value="<?= h($editItem['name'] ?? '') ?>" placeholder="e.g. Tapsilog">
            </div>
        </div>

        <button type="submit" class="btn"><?= $editItem ? 'Save changes' : '+ Add item' ?></button>
        <?php if ($editItem): ?>
            <a href="kitchen_items.php" class="btn secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($editItem): ?>
<div class="panel">
    <h2>Stock used per order
        <span style="font-weight:400; color:var(--muted); font-size:13px;">(optional — leave empty to just log counts)</span>
    </h2>

    <?php if (empty($recipe)): ?>
        <p style="color:var(--muted)">No stock linked yet — tallying this item won't affect stock levels.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Stock item</th><th>Qty per order</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($recipe as $r): ?>
                        <tr>
                            <td><?= h($r['stock_name']) ?></td>
                            <td><?= (int) $r['qty_per_order'] ?> <?= h($r['stock_unit']) ?></td>
                            <td>
                                <form method="post" action="kitchen_recipe_delete.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="kitchen_item_id" value="<?= (int) $editItem['id'] ?>">
                                    <button type="submit" class="btn small secondary">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="kitchen_recipe_save.php" class="form-row" style="margin-top:16px; align-items:flex-end;">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="kitchen_item_id" value="<?= (int) $editItem['id'] ?>">
        <div class="field">
            <label for="stock_item_id">Stock item</label>
            <select id="stock_item_id" name="stock_item_id" required>
                <option value="">— Select —</option>
                <?php foreach ($stockOptions as $si): ?>
                    <option value="<?= (int) $si['id'] ?>"><?= h($si['name']) ?> (<?= h($si['unit']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="max-width:150px;">
            <label for="qty_per_order">Qty per order</label>
            <input type="number" id="qty_per_order" name="qty_per_order" min="1" step="1" value="1" required>
        </div>
        <button type="submit" class="btn secondary">+ Link stock</button>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <h2>All kitchen items</h2>
    <?php if (empty($items)): ?>
        <p style="color:var(--muted)">No kitchen items yet — add one above.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Linked stock</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <?php $itCount = count(getKitchenRecipe((int) $it['id'])); ?>
                        <tr>
                            <td><?= h($it['name']) ?></td>
                            <td><?= $itCount ? $itCount . ' linked' : '—' ?></td>
                            <td><span class="pill <?= $it['is_active'] ? 'active' : 'inactive' ?>"><?= $it['is_active'] ? 'Active' : 'Retired' ?></span></td>
                            <td style="white-space:nowrap;">
                                <a href="kitchen_items.php?edit=<?= (int) $it['id'] ?>" class="btn small secondary">Edit</a>
                                <form method="post" action="kitchen_item_toggle.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
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
