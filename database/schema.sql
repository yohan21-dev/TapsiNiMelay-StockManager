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
-- Shifts (one shared shift open at a time for the whole crew)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shifts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    label       VARCHAR(100) NULL,
    opened_by   INT NOT NULL,
    opened_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_by   INT NULL,
    closed_at   TIMESTAMP NULL,
    FOREIGN KEY (opened_by) REFERENCES users(id),
    FOREIGN KEY (closed_by) REFERENCES users(id),
    INDEX idx_open (closed_at)
) ENGINE=InnoDB;

-- Tag every stock change with the shift it happened in (nullable so nothing
-- breaks for data recorded before shifts existed).
ALTER TABLE stock_logs
    ADD COLUMN shift_id INT NULL AFTER user_id,
    ADD FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Kitchen Count — the dishes staff tally as Dine In / Takeout-Delivery
-- orders go out, the digital version of the paper tally sheet.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kitchen_count_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Optional: link a kitchen item to the stock it consumes per order, so
-- tallying it can automatically deduct the right ingredients. Leave a
-- kitchen item with no rows here and it will just log counts.
CREATE TABLE IF NOT EXISTS kitchen_count_recipe (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    kitchen_count_item_id  INT NOT NULL,
    stock_item_id          INT NOT NULL,
    qty_per_order          INT NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_recipe (kitchen_count_item_id, stock_item_id),
    FOREIGN KEY (kitchen_count_item_id) REFERENCES kitchen_count_items(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- The running Dine In / Takeout tally per shift + item (mirrors
-- items.current_stock, but scoped to a single shift).
CREATE TABLE IF NOT EXISTS kitchen_counts (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    shift_id               INT NOT NULL,
    kitchen_count_item_id  INT NOT NULL,
    dine_in_count          INT NOT NULL DEFAULT 0,
    takeout_count          INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_shift_item (shift_id, kitchen_count_item_id),
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (kitchen_count_item_id) REFERENCES kitchen_count_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Audit trail of every tally tap (mirrors stock_logs).
CREATE TABLE IF NOT EXISTS kitchen_count_logs (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    shift_id               INT NOT NULL,
    kitchen_count_item_id  INT NOT NULL,
    order_type             ENUM('dine_in', 'takeout') NOT NULL,
    user_id                INT NOT NULL,
    change_amount          INT NOT NULL,
    resulting_count        INT NOT NULL,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (kitchen_count_item_id) REFERENCES kitchen_count_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_shift (shift_id)
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

-- Starter Kitchen Count items, based on the dine-in / takeout tally sheet
-- (edit/retire freely from Admin > Kitchen Items). None of these are linked
-- to stock yet — that's optional and done per item in the admin panel.
INSERT INTO kitchen_count_items (name) VALUES
    ('Egg & Rice'), ('Lugaw Egg'), ('Hungarian Silog'), ('Garlic Rice'),
    ('Goto / Lechon'), ('Chicken Silog'), ('Liempo'), ('Fruit Soda'),
    ('Tapsilog'), ('Tocilog'), ('2pcs Chicken'), ('Hungarian Tbangos'),
    ('Pork Silog'), ('Plain Rice'), ('Chicken Fillet Silog'), ('Lugaw Plain'),
    ('Bangsilog'), ('Spam Silog'), ('Nuggets Silog'), ('Hot Silog');
