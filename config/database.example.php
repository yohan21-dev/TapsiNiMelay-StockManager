<?php
/**
 * Database connection settings — TEMPLATE.
 *
 * Copy this file to "database.php" in the same folder and fill in your
 * real values. "database.php" is gitignored so your credentials never
 * get committed.
 *
 *   cp config/database.example.php config/database.php
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'tapsi_stock');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed. Check config/database.php — ' . $e->getMessage());
        }
    }

    return $pdo;
}
