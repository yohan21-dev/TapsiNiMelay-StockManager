# Tapsi Stock — Stock Management System

A simple web app for tracking business stock: tap **+** when stock comes in, tap **−** when it's used.
No more writing usage down on paper — everything is saved to a MySQL database with a full history,
login accounts, an admin panel, and usage reports.

## What's included

- **Login system** — admin and staff accounts, passwords hashed with bcrypt.
- **Shifts** (`shift.php`) — one shared shift for the whole crew at a time. Staff start a
  shift before they can record stock or kitchen counts, and end it when done. Every stock
  change and kitchen tally is tagged with the shift it happened in.
- **Dashboard** (`dashboard.php`) — the +/- stock counter for every active item, saved live to MySQL.
- **Kitchen Count** (`kitchen_count.php`) — the digital version of the paper tally sheet:
  tap + / − per dish, split into **Dine In** and **Takeout/Delivery**, for the open shift.
  Kitchen items can optionally be linked to stock ("uses 2 Eggs, 1 Rice") so tallying an
  order automatically deducts the right ingredients — or just leave a dish unlinked and
  it'll only log counts.
- **Reports** (`reports.php`) — daily/weekly usage totals per item with a date range picker,
  plus a **By shift** panel to pull up exactly what moved (stock + kitchen counts) during
  any specific shift.
- **Admin panel**
  - `admin/items.php` — add, edit, and retire stock items (name, category, unit, low-stock alert level).
  - `admin/kitchen_items.php` — add, edit, and retire the dishes staff tally in Kitchen
    Count, and link the stock each one uses (optional).
  - `admin/users.php` — add staff/admin accounts, reset passwords, enable/disable accounts.
- Every stock change is logged in `stock_logs` (who did it, when, which shift, and the
  resulting stock level) — this is what powers the reports and gives you a full audit trail.
  Every kitchen tally is logged the same way in `kitchen_count_logs`.

## Requirements

- PHP 8.0+ with the `pdo_mysql` extension (comes with XAMPP by default)
- MySQL / MariaDB (comes with XAMPP)

## Putting this on GitHub

This repo ships with `config/database.example.php` (safe to commit) and a `.gitignore`
that excludes the real `config/database.php` (your actual credentials). First-time setup:

```bash
git init
git add .
git commit -m "Initial commit: Tapsi Stock system"
git branch -M main
git remote add origin https://github.com/<your-username>/tapsi-stock.git
git push -u origin main
```

Anyone else cloning the repo (including you, on a new machine) then runs:

```bash
cp config/database.example.php config/database.php
# edit config/database.php with real DB credentials
```

**Never commit `config/database.php` with real credentials**, and rotate the DB password
if it's ever accidentally pushed. Suggested `.gitignore` (already included) covers this,
plus OS/editor junk files.

A private repo is recommended by default, since this handles business stock data — switch
to public only if you're fine with the code (not the data) being visible to anyone.

## Setup (XAMPP)


1. Copy the whole `tapsi-stock` folder into `htdocs` (e.g. `C:\xampp\htdocs\tapsi-stock`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), click **Import**, and import
   `database/schema.sql`. This creates the `tapsi_stock` database, all tables, a default
   admin account, a few starter categories, and a starter set of Kitchen Count items
   based on a typical dine-in/takeout menu (edit or retire these freely afterward).
4. If your MySQL has a root password (most XAMPP installs don't by default), open
   `config/database.php` and set `DB_PASS` accordingly.
5. Visit `http://localhost/tapsi-stock/` in your browser.
6. Log in with:
   - **Username:** `admin`
   - **Password:** `admin123`
7. **Change the admin password immediately** — go to Users, click Edit next to `admin`, and
   set a new password.

## Setup (cPanel / shared hosting)

1. Create a MySQL database and a database user in cPanel, and note the database name,
   username, and password (cPanel usually prefixes these, e.g. `cpaneluser_tapsi_stock`).
2. Open phpMyAdmin from cPanel, select your new database, and import `database/schema.sql`
   (you may need to remove the `CREATE DATABASE` / `USE` lines at the top since cPanel
   databases are pre-created).
3. Edit `config/database.php` with your actual `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. Upload the whole `tapsi-stock` folder via File Manager or FTP into `public_html`
   (or a subfolder if you want it at a sub-path).
5. Visit your domain and log in as above, then change the admin password.

## Upgrading an existing install

If you already have Tapsi Stock running and just pulled this update, your database is
missing the new `shifts` and `kitchen_count_*` tables. Run the migration once:

```bash
mysql -u root -p tapsi_stock < database/migration_shifts_kitchen.sql
```

(or paste its contents into phpMyAdmin's SQL tab with your database selected). This adds
the new tables, tags `stock_logs` with a `shift_id` column, and seeds some starter Kitchen
Count items — it does not touch your existing items, users, or stock history.

## Day-to-day use

- **Staff** log in, **start a shift** (Shift page — pick a name like "Morning" or leave it
  blank), then tap + / − on the **Stock** page to record trays, containers, or ingredients
  used or restocked, and tally orders on the **Kitchen Count** page as they go out (Dine In
  or Takeout/Delivery). Nothing needs to be written down. Stock and Kitchen Count are both
  locked until a shift is open — the header always shows whether one is.
- **Admins** additionally see Items and Kitchen Items (to add new things to track, set
  low-stock alerts, and optionally link a dish to the stock it uses) and Users (to add
  staff accounts or disable someone who leaves).
- The **Reports** page shows how much of each item was used per day or week over any
  date range, plus a **By shift** panel to pull up exactly what happened during any past
  or currently open shift — useful for reordering decisions, spotting waste, and closing
  out a shift's numbers.

## Notes on how stock changes are recorded

- Pressing **+** logs a positive change ("stock in" — e.g. new trays purchased).
- Pressing **−** logs a negative change ("stock out" — e.g. trays used for an order).
  Stock is never allowed to go below 0.
- Items are never hard-deleted from the database — "Retire" just hides them from the
  dashboard while keeping their full history intact for reports.

## Logo

- The site logo lives at `assets/img/logo.jpg` and is shown in the header (and on the
  login page). To swap it out, just replace that file with your own image — square
  images work best. If the file is ever missing, the header falls back to a plain
  text wordmark instead of breaking.

## Security notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- All forms are protected with CSRF tokens.
- All database queries use prepared statements (PDO) to prevent SQL injection.
- Change the default admin password before putting this in real use, and don't commit
  `config/database.php` with real credentials to a public repository.
