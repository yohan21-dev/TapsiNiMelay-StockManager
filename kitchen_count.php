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

<div class="kitchen-page">

    <a class="kitchen-shift-bar" href="shift.php">
        <span class="kitchen-shift-dot"></span>

        <span>
            <?= h(shiftDisplayLabel($shift)) ?>
            is open — tallies are saved to this shift.
        </span>
    </a>

    <?php if (empty($items)): ?>

        <div class="kitchen-empty">

            <div class="kitchen-empty-icon">
                <svg
                    width="36"
                    height="36"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>

            <h2>No kitchen items yet</h2>

            <p>
                <?php if (isAdmin()): ?>
                    Add the dishes staff tally in
                    <a href="admin/kitchen_items.php">Kitchen Items</a>.
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
                ?>

                <article
                    class="kc-card"
                    data-item-id="<?= (int) $item['id'] ?>"
                >

                    <div class="kc-top">

                        <div class="kc-name">
                            <?= h($item['name']) ?>
                        </div>

                        <?php if (!empty($item['recipe'])): ?>
                            <div class="kc-recipe">
                                Uses:
                                <?= h(implode(', ', array_map(
                                    static fn($r) =>
                                        (int) $r['qty_per_order']
                                        . ' '
                                        . $r['stock_name'],
                                    $item['recipe']
                                ))) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="kc-groups">

                        <div
                            class="kc-group"
                            data-order-type="dine_in"
                        >
                            <span class="kc-group-label">
                                Dine In
                            </span>

                            <div class="kc-controls">

                                <button
                                    type="button"
                                    class="count-button minus-button"
                                    data-action="minus"
                                    aria-label="Remove one dine-in <?= h($item['name']) ?>"
                                >
                                    −
                                </button>

                                <div
                                    class="count"
                                    data-role="count"
                                >
                                    <?= $dineIn ?>
                                </div>

                                <button
                                    type="button"
                                    class="count-button plus-button"
                                    data-action="plus"
                                    aria-label="Add one dine-in <?= h($item['name']) ?>"
                                >
                                    +
                                </button>

                            </div>
                        </div>

                        <div
                            class="kc-group"
                            data-order-type="takeout"
                        >
                            <span class="kc-group-label">
                                Takeout / Del
                            </span>

                            <div class="kc-controls">

                                <button
                                    type="button"
                                    class="count-button minus-button"
                                    data-action="minus"
                                    aria-label="Remove one takeout <?= h($item['name']) ?>"
                                >
                                    −
                                </button>

                                <div
                                    class="count"
                                    data-role="count"
                                >
                                    <?= $takeout ?>
                                </div>

                                <button
                                    type="button"
                                    class="count-button plus-button"
                                    data-action="plus"
                                    aria-label="Add one takeout <?= h($item['name']) ?>"
                                >
                                    +
                                </button>

                            </div>
                        </div>

                    </div>

                    <div class="kc-total">
                        <span class="kc-total-label">
                            Total Orders
                        </span>

                        <span
                            class="kc-total-value"
                            data-role="total"
                        >
                            <?= $total ?>
                        </span>
                    </div>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

</div>

<div
    class="kc-toast"
    id="kcToast"
    role="status"
    aria-live="polite"
></div>

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

    toastTimer = setTimeout(() => {
        kcToast.classList.remove('show');
    }, 2500);
}

function updateTotal(card) {
    let total = 0;

    card.querySelectorAll('.kc-group').forEach(group => {
        total += parseInt(
            group.querySelector('[data-role="count"]').textContent,
            10
        ) || 0;
    });

    const totalElement = card.querySelector('[data-role="total"]');

    if (totalElement) {
        totalElement.textContent = total;
    }
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

    const buttons = group.querySelectorAll('.count-button');

    buttons.forEach(b => b.disabled = true);

    card.classList.add('is-saving');

    try {

        const response = await fetch(
            'kitchen_count_update.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded'
                },

                body: new URLSearchParams({
                    kitchen_item_id: itemId,
                    order_type: orderType,
                    amount: amount,
                    csrf_token: CSRF_TOKEN
                })
            }
        );

        if (!response.ok) {
            throw new Error('HTTP error');
        }

        const data = await response.json();

        if (!data.success) {

            showKitchenToast(
                data.error || 'Could not update count.',
                true
            );

            return;
        }

        group.querySelector(
            '[data-role="count"]'
        ).textContent = data.new_count;

        updateTotal(card);

    } catch (error) {

        showKitchenToast(
            'Network error. Please try again.',
            true
        );

    } finally {

        buttons.forEach(b => b.disabled = false);

        card.classList.remove('is-saving');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>