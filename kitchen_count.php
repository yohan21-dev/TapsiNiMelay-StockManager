<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
requireOpenShift();

$pageTitle = 'Kitchen Count';
$pageSubtitle = 'Tally each order as it goes out — Dine In or Takeout/Delivery.';
$activeNav = 'kitchen';

$shift = getOpenShift();
$items = getKitchenCountsForShift((int) $shift['id']);

include __DIR__ . '/includes/header.php';
?>

<a class="shift-bar" href="shift.php">
    <span class="shift-dot on"></span>
    <?= h(shiftDisplayLabel($shift)) ?> is open — tallies are saved to this shift.
</a>

<?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
        </div>
        <h2>No kitchen items yet</h2>
        <p>
            <?php if (isAdmin()): ?>
                Add the dishes staff tally in <a href="admin/kitchen_items.php">Kitchen Items</a>.
            <?php else: ?>
                Ask an admin to add kitchen items before you can record counts.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <section class="kc-list" id="kcList">
        <?php foreach ($items as $item): ?>
            <article class="kc-card" data-item-id="<?= (int) $item['id'] ?>">
                <div class="kc-top">
                    <div class="kc-name"><?= h($item['name']) ?></div>
                    <?php if (!empty($item['recipe'])): ?>
                        <div class="kc-recipe">
                            Uses: <?= h(implode(', ', array_map(
                                static fn($r) => (int) $r['qty_per_order'] . ' ' . $r['stock_name'],
                                $item['recipe']
                            ))) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="kc-groups">
                    <div class="kc-group" data-order-type="dine_in">
                        <span class="kc-group-label">Dine In</span>
                        <div class="kc-controls">
                            <button class="count-button minus-button small" data-action="minus" aria-label="Remove one dine-in <?= h($item['name']) ?>">−</button>
                            <div class="count small" data-role="count"><?= (int) $item['dine_in_count'] ?></div>
                            <button class="count-button plus-button small" data-action="plus" aria-label="Add one dine-in <?= h($item['name']) ?>">+</button>
                        </div>
                    </div>
                    <div class="kc-group" data-order-type="takeout">
                        <span class="kc-group-label">Takeout/Del</span>
                        <div class="kc-controls">
                            <button class="count-button minus-button small" data-action="minus" aria-label="Remove one takeout <?= h($item['name']) ?>">−</button>
                            <div class="count small" data-role="count"><?= (int) $item['takeout_count'] ?></div>
                            <button class="count-button plus-button small" data-action="plus" aria-label="Add one takeout <?= h($item['name']) ?>">+</button>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
const CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;

document.getElementById('kcList')?.addEventListener('click', async (event) => {
    const button = event.target.closest('.count-button');
    if (!button) return;

    const group = button.closest('.kc-group');
    const card = button.closest('.kc-card');
    const itemId = card.dataset.itemId;
    const orderType = group.dataset.orderType;
    const action = button.dataset.action;
    const amount = action === 'plus' ? 1 : -1;

    group.querySelectorAll('.count-button').forEach(b => b.disabled = true);

    try {
        const response = await fetch('kitchen_count_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                kitchen_item_id: itemId,
                order_type: orderType,
                amount: amount,
                csrf_token: CSRF_TOKEN
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.error || 'Could not update count. Please try again.');
        } else {
            group.querySelector('[data-role="count"]').textContent = data.new_count;
        }
    } catch (err) {
        alert('Network error. Please check your connection and try again.');
    } finally {
        group.querySelectorAll('.count-button').forEach(b => b.disabled = false);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
