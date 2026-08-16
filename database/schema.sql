-- ============================================================
-- Tapsi Business — Stock Management System
-- Database schema
-- ============================================================
-- Import this file once in phpMyAdmin (or `mysql -u root -p < schema.sql`)
-- to create the database, tables, and a default admin account.
-- ============================================================

CREATE DATABASE IF NOT EXISTS tapsi_stock
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tapsi_stock;

-- ------------------------------------------------------------
-- Users (admin can manage stock + staff/items/reports; staff can only move stock)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Categories (e.g. Trays, Utensils, Packaging, Ingredients)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Items being tracked
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    category_id         INT NULL,
    unit                VARCHAR(20)  NOT NULL DEFAULT 'pcs',
    current_stock       INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Every stock movement (the audit trail / usage history)
-- change_amount: positive = stock added ("Stock In"),
--                negative = stock used/consumed ("Stock Out")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_logs (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    item_id          INT NOT NULL,
    user_id          INT NOT NULL,
    change_amount    INT NOT NULL,
    resulting_stock  INT NOT NULL,
    note             VARCHAR(255) NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_item_date (item_id, created_at),
    INDEX idx_date (created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data
-- ------------------------------------------------------------

-- Default admin account
--   username: admin
--   password: admin123   <-- CHANGE THIS immediately after first login (Admin > Users)
INSERT INTO users (username, password_hash, full_name, role)
VALUES ('admin', '$2y$10$RJXGIUn86d8qktqdCFXDuOy6QlMaQrB4GlTdfOHvzNhGwrpEURXf.', 'Administrator', 'admin');

-- A few starter categories (edit/delete freely from the admin panel)
INSERT INTO categories (name) VALUES ('Trays'), ('Packaging'), ('Utensils'), ('Ingredients');
