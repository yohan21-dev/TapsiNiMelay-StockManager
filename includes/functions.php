<?php
require_once __DIR__ . '/../config/database.php';

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** All active items with their category name, ordered for display. */
function getActiveItems(): array
{
    $db = getDB();
    $sql = "SELECT i.*, c.name AS category_name
            FROM items i
            LEFT JOIN categories c ON c.id = i.category_id
            WHERE i.is_active = 1
            ORDER BY c.name IS NULL, c.name, i.name";
    return $db->query($sql)->fetchAll();
}

function getAllCategories(): array
{
    return getDB()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

/**
 * Apply a stock change (positive = stock in, negative = usage/stock out)
 * and record it in the audit log, in a single transaction.
 */
function applyStockChange(int $itemId, int $userId, int $amount, string $note = '', ?int $shiftId = null): array
{
    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare('SELECT current_stock FROM items WHERE id = ? FOR UPDATE');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new RuntimeException('Item not found.');
        }

        $newStock = max(0, (int) $item['current_stock'] + $amount);

        $db->prepare('UPDATE items SET current_stock = ? WHERE id = ?')
           ->execute([$newStock, $itemId]);

        $db->prepare('INSERT INTO stock_logs (item_id, user_id, shift_id, change_amount, resulting_stock, note)
                       VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$itemId, $userId, $shiftId, $amount, $newStock, $note !== '' ? $note : null]);

        $db->commit();

        return ['success' => true, 'new_stock' => $newStock];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/** Daily usage totals (sum of negative changes, shown as positive "used" counts) grouped by item. */
function getUsageReport(string $startDate, string $endDate, string $groupBy = 'day'): array
{
    $db = getDB();

    $dateFormat = $groupBy === 'week' ? '%x-W%v' : '%Y-%m-%d';

    $sql = "SELECT
                i.id AS item_id,
                i.name AS item_name,
                DATE_FORMAT(l.created_at, '$dateFormat') AS period,
                SUM(CASE WHEN l.change_amount < 0 THEN -l.change_amount ELSE 0 END) AS used,
                SUM(CASE WHEN l.change_amount > 0 THEN l.change_amount ELSE 0 END) AS added
            FROM stock_logs l
            JOIN items i ON i.id = l.item_id
            WHERE DATE(l.created_at) BETWEEN ? AND ?
            GROUP BY i.id, period
            ORDER BY period DESC, i.name";

    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

/** Totals per item for the whole selected range (for the summary table). */
function getUsageTotals(string $startDate, string $endDate): array
{
    $db = getDB();
    $sql = "SELECT
                i.id, i.name, i.unit, i.current_stock, i.low_stock_threshold,
                COALESCE(SUM(CASE WHEN l.change_amount < 0 THEN -l.change_amount ELSE 0 END), 0) AS total_used,
                COALESCE(SUM(CASE WHEN l.change_amount > 0 THEN l.change_amount ELSE 0 END), 0) AS total_added
            FROM items i
            LEFT JOIN stock_logs l ON l.item_id = i.id AND DATE(l.created_at) BETWEEN ? AND ?
            WHERE i.is_active = 1
            GROUP BY i.id
            ORDER BY total_used DESC, i.name";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

function getRecentActivity(int $limit = 15): array
{
    $db = getDB();
    $sql = "SELECT l.*, i.name AS item_name, i.unit, u.full_name
            FROM stock_logs l
            JOIN items i ON i.id = l.item_id
            JOIN users u ON u.id = l.user_id
            ORDER BY l.created_at DESC
            LIMIT " . (int) $limit;
    return $db->query($sql)->fetchAll();
}

/** Stock totals used/added for a single shift only (for the shift report). */
function getUsageTotalsByShift(int $shiftId): array
{
    $db = getDB();
    $sql = "SELECT i.id, i.name, i.unit, i.current_stock, i.low_stock_threshold,
                   COALESCE(SUM(CASE WHEN l.change_amount < 0 THEN -l.change_amount ELSE 0 END), 0) AS total_used,
                   COALESCE(SUM(CASE WHEN l.change_amount > 0 THEN l.change_amount ELSE 0 END), 0) AS total_added
            FROM items i
            JOIN stock_logs l ON l.item_id = i.id AND l.shift_id = ?
            GROUP BY i.id
            ORDER BY total_used DESC, i.name";
    $stmt = $db->prepare($sql);
    $stmt->execute([$shiftId]);
    return $stmt->fetchAll();
}

/* ============================================================
 * Shifts
 * One shared shift is open for the whole crew at a time. Staff must
 * start a shift before recording stock or kitchen counts.
 * ============================================================ */

/** The currently open shift, or null if none is open. */
function getOpenShift(): ?array
{
    $db = getDB();
    $stmt = $db->query("SELECT s.*, u.full_name AS opened_by_name
                         FROM shifts s
                         JOIN users u ON u.id = s.opened_by
                         WHERE s.closed_at IS NULL
                         ORDER BY s.opened_at DESC
                         LIMIT 1");
    return $stmt->fetch() ?: null;
}

/** Redirect to the Shift page if nobody has started a shift yet. Call after requireLogin(). */
function requireOpenShift(): void
{
    if (!getOpenShift()) {
        header('Location: shift.php?need_shift=1');
        exit;
    }
}

function startShift(int $userId, string $label = ''): array
{
    if (getOpenShift()) {
        return ['success' => false, 'error' => 'A shift is already open.'];
    }
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO shifts (label, opened_by) VALUES (?, ?)');
    $stmt->execute([$label !== '' ? $label : null, $userId]);
    return ['success' => true, 'shift_id' => (int) $db->lastInsertId()];
}

function closeShift(int $shiftId, int $userId): array
{
    $db = getDB();
    $stmt = $db->prepare('UPDATE shifts SET closed_at = NOW(), closed_by = ? WHERE id = ? AND closed_at IS NULL');
    $stmt->execute([$userId, $shiftId]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'That shift is already closed.'];
    }
    return ['success' => true];
}

function getShift(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u1.full_name AS opened_by_name, u2.full_name AS closed_by_name
                           FROM shifts s
                           JOIN users u1 ON u1.id = s.opened_by
                           LEFT JOIN users u2 ON u2.id = s.closed_by
                           WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getShiftHistory(int $limit = 30): array
{
    $db = getDB();
    $sql = "SELECT s.*, u1.full_name AS opened_by_name, u2.full_name AS closed_by_name
            FROM shifts s
            JOIN users u1 ON u1.id = s.opened_by
            LEFT JOIN users u2 ON u2.id = s.closed_by
            ORDER BY s.opened_at DESC
            LIMIT " . (int) $limit;
    return $db->query($sql)->fetchAll();
}

/** A friendly label for a shift: its custom name, or "Morning/Afternoon/Evening shift" based on when it opened. */
function shiftDisplayLabel(array $shift): string
{
    if (!empty($shift['label'])) {
        return $shift['label'];
    }
    $hour = (int) date('G', strtotime($shift['opened_at']));
    if ($hour < 11) return 'Morning shift';
    if ($hour < 17) return 'Afternoon shift';
    return 'Evening shift';
}

/* ============================================================
 * Kitchen Count
 * The digital version of the paper tally sheet: staff tap + / - per
 * dish, split into Dine In and Takeout/Delivery, for the open shift.
 * ============================================================ */

function getActiveStockItemsForSelect(): array
{
    return getDB()->query('SELECT id, name, unit FROM items WHERE is_active = 1 ORDER BY name')->fetchAll();
}

function getActiveKitchenItems(): array
{
    return getDB()->query('SELECT * FROM kitchen_count_items WHERE is_active = 1 ORDER BY name')->fetchAll();
}

function getAllKitchenItems(): array
{
    return getDB()->query('SELECT * FROM kitchen_count_items ORDER BY is_active DESC, name')->fetchAll();
}

/** The stock ingredients linked to a kitchen item, if any (optional recipe). */
function getKitchenRecipe(int $kitchenItemId): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT r.*, i.name AS stock_name, i.unit AS stock_unit
                           FROM kitchen_count_recipe r
                           JOIN items i ON i.id = r.stock_item_id
                           WHERE r.kitchen_count_item_id = ?
                           ORDER BY i.name');
    $stmt->execute([$kitchenItemId]);
    return $stmt->fetchAll();
}

/** Active kitchen items with their current Dine In / Takeout tally for the given shift (0 if not tapped yet). */
function getKitchenCountsForShift(int $shiftId): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT k.id, k.name,
                                  COALESCE(c.dine_in_count, 0) AS dine_in_count,
                                  COALESCE(c.takeout_count, 0) AS takeout_count
                           FROM kitchen_count_items k
                           LEFT JOIN kitchen_counts c ON c.kitchen_count_item_id = k.id AND c.shift_id = ?
                           WHERE k.is_active = 1
                           ORDER BY k.name");
    $stmt->execute([$shiftId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['recipe'] = getKitchenRecipe((int) $row['id']);
    }
    unset($row);

    return $rows;
}

/** Only the items that actually have a tally this shift (for the shift report). */
function getKitchenReportForShift(int $shiftId): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT k.name, c.dine_in_count, c.takeout_count
                           FROM kitchen_counts c
                           JOIN kitchen_count_items k ON k.id = c.kitchen_count_item_id
                           WHERE c.shift_id = ? AND (c.dine_in_count > 0 OR c.takeout_count > 0)
                           ORDER BY k.name");
    $stmt->execute([$shiftId]);
    return $stmt->fetchAll();
}

/**
 * Record one tally tap (+1 or -1) for a kitchen item, split by order type,
 * for the given shift. If that kitchen item has linked stock ingredients
 * (a "recipe"), the matching stock is deducted (or restored, on undo)
 * automatically, in the same transaction.
 */
function applyKitchenCount(int $shiftId, int $kitchenItemId, string $orderType, int $userId, int $amount): array
{
    if (!in_array($orderType, ['dine_in', 'takeout'], true)) {
        return ['success' => false, 'error' => 'Invalid order type.'];
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $column = $orderType === 'dine_in' ? 'dine_in_count' : 'takeout_count';

        $db->prepare('INSERT INTO kitchen_counts (shift_id, kitchen_count_item_id, dine_in_count, takeout_count)
                       VALUES (?, ?, 0, 0)
                       ON DUPLICATE KEY UPDATE id = id')
           ->execute([$shiftId, $kitchenItemId]);

        $stmt = $db->prepare("SELECT $column FROM kitchen_counts WHERE shift_id = ? AND kitchen_count_item_id = ? FOR UPDATE");
        $stmt->execute([$shiftId, $kitchenItemId]);
        $current = (int) $stmt->fetchColumn();

        $newCount = max(0, $current + $amount);
        $actualChange = $newCount - $current; // 0 if the tap was clamped at the floor

        $db->prepare("UPDATE kitchen_counts SET $column = ? WHERE shift_id = ? AND kitchen_count_item_id = ?")
           ->execute([$newCount, $shiftId, $kitchenItemId]);

        $db->prepare('INSERT INTO kitchen_count_logs (shift_id, kitchen_count_item_id, order_type, user_id, change_amount, resulting_count)
                       VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$shiftId, $kitchenItemId, $orderType, $userId, $actualChange, $newCount]);

        if ($actualChange !== 0) {
            foreach (getKitchenRecipe($kitchenItemId) as $ingredient) {
                $stockChange = -1 * (int) $ingredient['qty_per_order'] * $actualChange;
                if ($stockChange === 0) {
                    continue;
                }

                $stmt = $db->prepare('SELECT current_stock FROM items WHERE id = ? FOR UPDATE');
                $stmt->execute([$ingredient['stock_item_id']]);
                $stockItem = $stmt->fetch();
                if (!$stockItem) {
                    continue;
                }

                $newStock = max(0, (int) $stockItem['current_stock'] + $stockChange);

                $db->prepare('UPDATE items SET current_stock = ? WHERE id = ?')
                   ->execute([$newStock, $ingredient['stock_item_id']]);

                $db->prepare('INSERT INTO stock_logs (item_id, user_id, shift_id, change_amount, resulting_stock, note)
                               VALUES (?, ?, ?, ?, ?, ?)')
                   ->execute([
                       $ingredient['stock_item_id'],
                       $userId,
                       $shiftId,
                       $stockChange,
                       $newStock,
                       'Auto-deducted from Kitchen Count',
                   ]);
            }
        }

        $db->commit();
        return ['success' => true, 'new_count' => $newCount];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
