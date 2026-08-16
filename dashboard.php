<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'Stock';
$pageSubtitle = 'Tap + when stock arrives, tap − when it\'s used. Every tap is saved automatically.';
$activeNav = 'dashboard';
$items = getActiveItems();

include __DIR__ . '/includes/header.php';
?>

<?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">📦</div>
        <h2>No items yet</h2>
        <p>
            <?php if (isAdmin()): ?>
                Add your first item in <a href="admin/items.php">Items</a> to start tracking stock.
            <?php else: ?>
                Ask an admin to add items before you can record stock.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <section class="counter-list" id="counterList">
        <?php foreach ($items as $item): ?>
            <?php $isLow = (int)$item['current_stock'] <= (int)$item['low_stock_threshold']; ?>
            <article class="counter-card<?= $isLow ? ' low-stock' : '' ?>" data-item-id="<?= (int)$item['id'] ?>">
                <div class="counter-top">
                    <div>
                        <div class="counter-name">
                            <?= h($item['name']) ?>
                            <?php if ($isLow): ?><span class="badge low">Low stock</span><?php endif; ?>
                        </div>
                        <div class="counter-meta">
                            <?= h($item['category_name'] ?? 'Uncategorized') ?> · unit: <?= h($item['unit']) ?>
                        </div>
                    </div>
                </div>

                <div class="counter-controls">
                    <button class="count-button minus-button" data-action="minus" aria-label="Use one <?= h($item['name']) ?>">−</button>
                    <div class="count" data-role="count">
                        <?= (int)$item['current_stock'] ?>
                        <span class="count-unit"><?= h($item['unit']) ?></span>
                    </div>
                    <button class="count-button plus-button" data-action="plus" aria-label="Add one <?= h($item['name']) ?>">+</button>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
const CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;

document.getElementById('counterList')?.addEventListener('click', async (event) => {
    const button = event.target.closest('.count-button');
    if (!button) return;

    const card = button.closest('.counter-card');
    const itemId = card.dataset.itemId;
    const action = button.dataset.action;
    const amount = action === 'plus' ? 1 : -1;

    // Prevent double taps while the request is in flight
    card.querySelectorAll('.count-button').forEach(b => b.disabled = true);

    try {
        const response = await fetch('update_stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                item_id: itemId,
                amount: amount,
                csrf_token: CSRF_TOKEN
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.error || 'Could not update stock. Please try again.');
        } else {
            const countEl = card.querySelector('[data-role="count"]');
            const unit = countEl.querySelector('.count-unit').outerHTML;
            countEl.innerHTML = data.new_stock + unit;

            const isLow = data.new_stock <= data.threshold;
            card.classList.toggle('low-stock', isLow);
            const nameEl = card.querySelector('.counter-name');
            const existingBadge = nameEl.querySelector('.badge');
            if (isLow && !existingBadge) {
                nameEl.insertAdjacentHTML('beforeend', ' <span class="badge low">Low stock</span>');
            } else if (!isLow && existingBadge) {
                existingBadge.remove();
            }
        }
    } catch (err) {
        alert('Network error. Please check your connection and try again.');
    } finally {
        card.querySelectorAll('.count-button').forEach(b => b.disabled = false);
    }
});
</script>

<?php if (isAdmin()): ?>
    <div class="panel" style="margin-top:20px;">
        <h2>Recent activity</h2>
        <?php $recent = getRecentActivity(15); ?>
        <?php if (empty($recent)): ?>
            <p style="color:var(--muted)">No activity yet.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>When</th><th>Item</th><th>Change</th><th>By</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $log): ?>
                            <tr>
                                <td><?= h(date('M j, g:i A', strtotime($log['created_at']))) ?></td>
                                <td><?= h($log['item_name']) ?></td>
                                <td><?= $log['change_amount'] > 0 ? '+' : '' ?><?= (int)$log['change_amount'] ?> <?= h($log['unit']) ?></td>
                                <td><?= h($log['full_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
