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
