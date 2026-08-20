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

$grandDineIn = 0;
$grandTakeout = 0;
foreach ($items as $item) {
    $grandDineIn += (int) $item['dine_in_count'];
    $grandTakeout += (int) $item['takeout_count'];
}
$grandTotal = $grandDineIn + $grandTakeout;

include __DIR__ . '/includes/header.php';
?>

<div class="kitchen-page">

    <a class="kitchen-shift-bar" href="shift.php">
        <span class="kitchen-shift-dot"></span>
        <span><?= h(shiftDisplayLabel($shift)) ?> is open — tallies are saved to this shift.</span>
    </a>

    <?php if (!empty($items)): ?>
        <div class="stats-bar" id="kcStatsBar">
            <div class="stat-chip accent">
                <div class="stat-chip-value" data-role="grand-dine-in"><?= $grandDineIn ?></div>
                <div class="stat-chip-label">Dine In</div>
            </div>
            <div class="stat-chip accent">
                <div class="stat-chip-value" data-role="grand-takeout"><?= $grandTakeout ?></div>
                <div class="stat-chip-label">Takeout / Del</div>
            </div>
            <div class="stat-chip">
                <div class="stat-chip-value" data-role="grand-total"><?= $grandTotal ?></div>
                <div class="stat-chip-label">Total Orders</div>
            </div>
        </div>

        <div class="kc-search-wrap">
            <div class="search-bar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
                <input type="search" id="kitchenSearch" placeholder="Search dishes..." aria-label="Search kitchen items">
                <button type="button" class="clear-search" id="clearKitchenSearch" aria-label="Clear search">✕</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>

        <div class="kitchen-empty">
            <div class="kitchen-empty-icon">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
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
                <?php
                $dineIn = (int) $item['dine_in_count'];
                $takeout = (int) $item['takeout_count'];
                $total = $dineIn + $takeout;
                $initial = mb_strtoupper(mb_substr($item['name'], 0, 1));
                ?>

                <article class="kc-card" data-item-id="<?= (int) $item['id'] ?>" data-name="<?= h(mb_strtolower($item['name'])) ?>">

                    <div class="kc-top">
                        <div class="item-avatar" aria-hidden="true"><?= h($initial) ?></div>
                        <div class="kc-top-text">
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
                    </div>

                    <div class="kc-groups">

                        <div class="kc-group" data-order-type="dine_in">
                            <span class="kc-group-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"></path>
                                    <path d="M7 2v20"></path>
                                    <path d="M17 2a4 4 0 0 0-4 4v6h8"></path>
                                    <path d="M17 12v10"></path>
                                </svg>
                                Dine In
                            </span>
                            <div class="kc-controls">
                                <button type="button" class="count-button minus-button" data-action="minus" aria-label="Remove one dine-in <?= h($item['name']) ?>">−</button>
                                <div class="count" data-role="count"><?= $dineIn ?></div>
                                <button type="button" class="count-button plus-button" data-action="plus" aria-label="Add one dine-in <?= h($item['name']) ?>">+</button>
                            </div>
                        </div>

                        <div class="kc-group" data-order-type="takeout">
                            <span class="kc-group-label">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                    <path d="M3 6h18"></path>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                Takeout / Del
                            </span>
                            <div class="kc-controls">
                                <button type="button" class="count-button minus-button" data-action="minus" aria-label="Remove one takeout <?= h($item['name']) ?>">−</button>
                                <div class="count" data-role="count"><?= $takeout ?></div>
                                <button type="button" class="count-button plus-button" data-action="plus" aria-label="Add one takeout <?= h($item['name']) ?>">+</button>
                            </div>
                        </div>

                    </div>

                    <div class="kc-total">
                        <span class="kc-total-label">Total Orders</span>
                        <span class="kc-total-value" data-role="total"><?= $total ?></span>
                    </div>

                </article>
            <?php endforeach; ?>
        </section>

        <div class="no-results" id="kcNoResults" style="display:none;">No dishes match your search.</div>

    <?php endif; ?>

</div>

<div class="kc-toast" id="kcToast" role="status" aria-live="polite"></div>

<script>
const CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;

const kcList = document.getElementById('kcList');
const kcToast = document.getElementById('kcToast');

let toastTimer = null;

function showKitchenToast(message, isError = false) {
    if (!kcToast) return;
    clearTimeout(toastTimer);
    kcToast.textContent = message;
    kcToast.classList.toggle('error', isError);
    kcToast.classList.add('show');
    toastTimer = setTimeout(() => kcToast.classList.remove('show'), 2500);
}

function updateTotal(card) {
    let total = 0;
    card.querySelectorAll('.kc-group').forEach(group => {
        total += parseInt(group.querySelector('[data-role="count"]').textContent, 10) || 0;
    });
    const totalElement = card.querySelector('[data-role="total"]');
    if (totalElement) totalElement.textContent = total;
}

function adjustGrandTotal(orderType, delta) {
    if (!delta) return;
    const key = orderType === 'dine_in' ? 'grand-dine-in' : 'grand-takeout';
    const el = document.querySelector(`[data-role="${key}"]`);
    const totalEl = document.querySelector('[data-role="grand-total"]');
    if (el) el.textContent = (parseInt(el.textContent, 10) || 0) + delta;
    if (totalEl) totalEl.textContent = (parseInt(totalEl.textContent, 10) || 0) + delta;
}

kcList?.addEventListener('click', async (event) => {
    const button = event.target.closest('.count-button');
    if (!button || button.disabled) return;

    const group = button.closest('.kc-group');
    const card = button.closest('.kc-card');
    if (!group || !card) return;

    const itemId = card.dataset.itemId;
    const orderType = group.dataset.orderType;
    const action = button.dataset.action;
    const amount = action === 'plus' ? 1 : -1;

    const countEl = group.querySelector('[data-role="count"]');
    const previousValue = parseInt(countEl.textContent, 10) || 0;

    const buttons = group.querySelectorAll('.count-button');
    buttons.forEach(b => b.disabled = true);
    card.classList.add('is-saving');

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

        if (!response.ok) throw new Error('HTTP error');

        const data = await response.json();

        if (!data.success) {
            showKitchenToast(data.error || 'Could not update count.', true);
            return;
        }

        countEl.textContent = data.new_count;
        updateTotal(card);
        adjustGrandTotal(orderType, data.new_count - previousValue);

    } catch (error) {
        showKitchenToast('Network error. Please try again.', true);
    } finally {
        buttons.forEach(b => b.disabled = false);
        card.classList.remove('is-saving');
    }
});

// --- Search filtering ---------------------------------------------
(function () {
    const searchInput = document.getElementById('kitchenSearch');
    const clearButton = document.getElementById('clearKitchenSearch');
    const noResults = document.getElementById('kcNoResults');
    const cards = Array.from(document.querySelectorAll('.kc-card'));
    if (!cards.length) return;

    function applyFilter() {
        const query = (searchInput?.value || '').trim().toLowerCase();
        if (clearButton) clearButton.style.display = query ? 'flex' : 'none';

        let visibleCount = 0;
        cards.forEach(card => {
            const show = !query || card.dataset.name.includes(query);
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    searchInput?.addEventListener('input', applyFilter);
    clearButton?.addEventListener('click', () => {
        if (!searchInput) return;
        searchInput.value = '';
        searchInput.focus();
        applyFilter();
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>