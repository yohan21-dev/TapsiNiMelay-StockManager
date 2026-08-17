-- ============================================================
-- Migration: Shifts + Kitchen Count
-- ============================================================
-- Run this ONCE against your EXISTING tapsi_stock database to add the
-- new Shift and Kitchen Count feature without losing your current data.
--
--   mysql -u root -p tapsi_stock < database/migration_shifts_kitchen.sql
--
-- (or paste it into phpMyAdmin > SQL tab with your database selected)
--
-- If you are setting up a brand new install instead, just import
-- database/schema.sql — it already includes everything below.
-- ============================================================

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

-- Tag stock changes with the shift they happened in.
-- Skip this ALTER if you've already run this migration once before
-- (it will error with "duplicate column" if shift_id already exists).
ALTER TABLE stock_logs
    ADD COLUMN shift_id INT NULL AFTER user_id,
    ADD FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Kitchen Count
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kitchen_count_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kitchen_count_recipe (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    kitchen_count_item_id  INT NOT NULL,
    stock_item_id          INT NOT NULL,
    qty_per_order          INT NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_recipe (kitchen_count_item_id, stock_item_id),
    FOREIGN KEY (kitchen_count_item_id) REFERENCES kitchen_count_items(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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

-- Starter Kitchen Count items based on the paper tally sheet. Safe to
-- run once; if you re-run this migration, delete these lines first or
-- you'll get duplicate rows (there's no unique constraint on name).
INSERT INTO kitchen_count_items (name) VALUES
    ('Egg & Rice'), ('Lugaw Egg'), ('Hungarian Silog'), ('Garlic Rice'),
    ('Goto / Lechon'), ('Chicken Silog'), ('Liempo'), ('Fruit Soda'),
    ('Tapsilog'), ('Tocilog'), ('2pcs Chicken'), ('Hungarian Tbangos'),
    ('Pork Silog'), ('Plain Rice'), ('Chicken Fillet Silog'), ('Lugaw Plain'),
    ('Bangsilog'), ('Spam Silog'), ('Nuggets Silog'), ('Hot Silog');
