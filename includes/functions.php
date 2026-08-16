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
function applyStockChange(int $itemId, int $userId, int $amount, string $note = ''): array
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

        $db->prepare('INSERT INTO stock_logs (item_id, user_id, change_amount, resulting_stock, note)
                       VALUES (?, ?, ?, ?, ?)')
           ->execute([$itemId, $userId, $amount, $newStock, $note !== '' ? $note : null]);

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
