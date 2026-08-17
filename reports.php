<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'Reports';
$pageSubtitle = 'See how much stock is being used, by day or by week.';
$activeNav = 'reports';
$wide = true;

$range = $_GET['range'] ?? '7d';
$groupBy = $_GET['group'] ?? 'day';

$today = new DateTime('today');
switch ($range) {
    case '30d':
        $start = (clone $today)->modify('-29 days');
        break;
    case '90d':
        $start = (clone $today)->modify('-89 days');
        break;
    case 'custom':
        $start = DateTime::createFromFormat('Y-m-d', $_GET['start'] ?? '') ?: (clone $today)->modify('-6 days');
        $today = DateTime::createFromFormat('Y-m-d', $_GET['end'] ?? '') ?: $today;
        break;
    case '7d':
    default:
        $start = (clone $today)->modify('-6 days');
        break;
}

$startDate = $start->format('Y-m-d');
$endDate = $today->format('Y-m-d');

$totals = getUsageTotals($startDate, $endDate);
$breakdown = getUsageReport($startDate, $endDate, $groupBy === 'week' ? 'week' : 'day');

// Shift report: lets a report "indicate the start of a specific shift" —
// pick a shift and see exactly what moved during it.
$shifts = getShiftHistory(50);
$shiftIdParam = filter_input(INPUT_GET, 'shift', FILTER_VALIDATE_INT);
$selectedShift = null;
if ($shiftIdParam) {
    $selectedShift = getShift($shiftIdParam);
} elseif (!empty($shifts)) {
    $selectedShift = getOpenShift() ?: $shifts[0];
}
$shiftStockTotals = $selectedShift ? getUsageTotalsByShift((int) $selectedShift['id']) : [];
$shiftKitchenTotals = $selectedShift ? getKitchenReportForShift((int) $selectedShift['id']) : [];

// Pivot the breakdown into: periods (columns) x items (rows) for a readable table
$periods = [];
$pivot = []; // pivot[item_name][period] = used
foreach ($breakdown as $row) {
    $periods[$row['period']] = true;
    $pivot[$row['item_name']][$row['period']] = (int)$row['used'];
}
$periods = array_keys($periods);
rsort($periods);

include __DIR__ . '/includes/header.php';
?>

<div class="panel">
    <h2>By shift</h2>
    <?php if (empty($shifts)): ?>
        <p style="color:var(--muted)">No shifts recorded yet — start one from the <a href="shift.php">Shift</a> page.</p>
    <?php else: ?>
        <form method="get" class="field" style="max-width:400px; margin-bottom:16px;">
            <label for="shift">Choose a shift</label>
            <select name="shift" id="shift" onchange="this.form.submit()">
                <?php foreach ($shifts as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($selectedShift && (int) $selectedShift['id'] === (int) $s['id']) ? 'selected' : '' ?>>
                        <?= h(shiftDisplayLabel($s)) ?> — <?= h(date('M j, g:i A', strtotime($s['opened_at']))) ?><?= $s['closed_at'] ? '' : ' (open)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedShift): ?>
            <p class="shift-meta" style="margin-top:-6px;">
                Opened by <?= h($selectedShift['opened_by_name']) ?>
                <?php if ($selectedShift['closed_at']): ?>
                    · closed by <?= h($selectedShift['closed_by_name']) ?> at <?= h(date('M j, g:i A', strtotime($selectedShift['closed_at']))) ?>
                <?php else: ?>
                    · still open
                <?php endif; ?>
            </p>

            <h3 style="font-size:14px; margin:18px 0 10px;">Stock moved</h3>
            <?php if (empty($shiftStockTotals)): ?>
                <p style="color:var(--muted)">No stock changes recorded this shift.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Item</th><th>Used</th><th>Added</th></tr></thead>
                        <tbody>
                            <?php foreach ($shiftStockTotals as $t): ?>
                                <tr>
                                    <td><?= h($t['name']) ?></td>
                                    <td><?= (int) $t['total_used'] ?> <?= h($t['unit']) ?></td>
                                    <td><?= (int) $t['total_added'] ?> <?= h($t['unit']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h3 style="font-size:14px; margin:18px 0 10px;">Kitchen counts</h3>
            <?php if (empty($shiftKitchenTotals)): ?>
                <p style="color:var(--muted)">No kitchen counts recorded this shift.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Item</th><th>Dine In</th><th>Takeout/Del</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($shiftKitchenTotals as $k): ?>
                                <tr>
                                    <td><?= h($k['name']) ?></td>
                                    <td><?= (int) $k['dine_in_count'] ?></td>
                                    <td><?= (int) $k['takeout_count'] ?></td>
                                    <td><?= (int) $k['dine_in_count'] + (int) $k['takeout_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form method="get" class="panel">
    <div class="actions-row" style="margin-bottom:0;">
        <div class="field" style="min-width:160px; margin-bottom:0;">
            <label for="range">Date range</label>
            <select name="range" id="range" onchange="this.form.submit()">
                <option value="7d" <?= $range === '7d' ? 'selected' : '' ?>>Last 7 days</option>
                <option value="30d" <?= $range === '30d' ? 'selected' : '' ?>>Last 30 days</option>
                <option value="90d" <?= $range === '90d' ? 'selected' : '' ?>>Last 90 days</option>
                <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <?php if ($range === 'custom'): ?>
            <div class="field" style="min-width:150px; margin-bottom:0;">
                <label for="start">From</label>
                <input type="date" name="start" id="start" value="<?= h($startDate) ?>">
            </div>
            <div class="field" style="min-width:150px; margin-bottom:0;">
                <label for="end">To</label>
                <input type="date" name="end" id="end" value="<?= h($endDate) ?>">
            </div>
        <?php endif; ?>
        <div class="field" style="min-width:140px; margin-bottom:0;">
            <label for="group">Group by</label>
            <select name="group" id="group" onchange="this.form.submit()">
                <option value="day" <?= $groupBy !== 'week' ? 'selected' : '' ?>>Day</option>
                <option value="week" <?= $groupBy === 'week' ? 'selected' : '' ?>>Week</option>
            </select>
        </div>
        <?php if ($range === 'custom'): ?>
            <button type="submit" class="btn small">Apply</button>
        <?php endif; ?>
    </div>
</form>

<div class="panel">
    <h2>Summary: <?= h($startDate) ?> to <?= h($endDate) ?></h2>
    <?php if (empty($totals)): ?>
        <p style="color:var(--muted)">No items to report on yet.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Used</th>
                        <th>Added</th>
                        <th>Current stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($totals as $t): ?>
                        <?php $isLow = (int)$t['current_stock'] <= (int)$t['low_stock_threshold']; ?>
                        <tr>
                            <td><?= h($t['name']) ?></td>
                            <td><?= (int)$t['total_used'] ?> <?= h($t['unit']) ?></td>
                            <td><?= (int)$t['total_added'] ?> <?= h($t['unit']) ?></td>
                            <td><?= (int)$t['current_stock'] ?> <?= h($t['unit']) ?></td>
                            <td><span class="pill <?= $isLow ? 'inactive' : 'active' ?>"><?= $isLow ? 'Low' : 'OK' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Usage by <?= $groupBy === 'week' ? 'week' : 'day' ?></h2>
    <?php if (empty($periods)): ?>
        <p style="color:var(--muted)">No usage recorded in this range.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <?php foreach ($periods as $p): ?><th><?= h($p) ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pivot as $itemName => $row): ?>
                        <tr>
                            <td><?= h($itemName) ?></td>
                            <?php foreach ($periods as $p): ?>
                                <td><?= isset($row[$p]) ? (int)$row[$p] : '—' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
