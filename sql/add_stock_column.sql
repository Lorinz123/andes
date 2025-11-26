-- add_stock_column.sql
-- Adds a `stock` column to the existing `products` table (if missing).

ALTER TABLE `products`
  ADD COLUMN `stock` INT NOT NULL DEFAULT 0 AFTER `price`;

-- Run in phpMyAdmin or mysql CLI:
-- mysql -u root -p andes_db < add_stock_column.sql
