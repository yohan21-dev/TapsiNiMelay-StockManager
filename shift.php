<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'Shift';
$pageSubtitle = 'Start a shift before recording stock or kitchen counts.';
$activeNav = 'shift';

$openShift = getOpenShift();
$needShift = isset($_GET['need_shift']) && !$openShift;
$history = getShiftHistory(15);

include __DIR__ . '/includes/header.php';
?>

<?php if ($needShift): ?>
    <div class="error-box">Start a shift before you can record stock or kitchen counts.</div>
<?php endif; ?>

<?php if ($openShift): ?>
    <div class="panel shift-panel">
        <div class="shift-status">
            <span class="shift-dot on"></span>
            <div>
                <h2 style="margin:0;"><?= h(shiftDisplayLabel($openShift)) ?></h2>
                <p class="shift-meta">
                    Opened by <?= h($openShift['opened_by_name']) ?> ·
                    <span class="shift-duration" data-opened-at="<?= h($openShift['opened_at']) ?>">just now</span>
                </p>
            </div>
        </div>
        <form method="post" action="shift_close.php"
              onsubmit="return confirm('End the current shift? Staff will need to start a new one before recording stock or kitchen counts.');">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int) $openShift['id'] ?>">
            <button type="submit" class="btn danger">End Shift</button>
        </form>
    </div>
<?php else: ?>
    <div class="panel shift-panel">
        <div class="shift-status">
            <span class="shift-dot off"></span>
            <div>
                <h2 style="margin:0;">No active shift</h2>
                <p class="shift-meta">Start one to begin recording stock and kitchen counts.</p>
            </div>
        </div>
        <form method="post" action="shift_start.php" class="shift-start-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="field" style="margin-bottom:0;">
                <label for="label">Shift name (optional)</label>
                <input type="text" id="label" name="label" maxlength="100" placeholder="e.g. Morning, Dinner Rush">
            </div>
            <button type="submit" class="btn">Start Shift</button>
        </form>
    </div>
<?php endif; ?>

<div class="panel">
    <h2>Shift history</h2>
    <?php if (empty($history)): ?>
        <p style="color:var(--muted)">No shifts recorded yet.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Shift</th><th>Opened</th><th>Closed</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($history as $s): ?>
                        <tr>
                            <td><?= h(shiftDisplayLabel($s)) ?></td>
                            <td><?= h($s['opened_by_name']) ?> · <?= h(date('M j, g:i A', strtotime($s['opened_at']))) ?></td>
                            <td><?= $s['closed_at'] ? h($s['closed_by_name']) . ' · ' . h(date('M j, g:i A', strtotime($s['closed_at']))) : '—' ?></td>
                            <td><span class="pill <?= $s['closed_at'] ? 'inactive' : 'active' ?>"><?= $s['closed_at'] ? 'Closed' : 'Open' ?></span></td>
                            <td><a href="reports.php?shift=<?= (int) $s['id'] ?>" class="btn small secondary">View report</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.shift-duration').forEach((el) => {
    const opened = new Date(el.dataset.openedAt.replace(' ', 'T'));
    function tick() {
        const diffMs = Date.now() - opened.getTime();
        const mins = Math.max(0, Math.floor(diffMs / 60000));
        const hrs = Math.floor(mins / 60);
        const rem = mins % 60;
        el.textContent = (hrs > 0 ? hrs + 'h ' : '') + rem + 'm ago';
    }
    tick();
    setInterval(tick, 30000);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
