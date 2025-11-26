-- create_admin_users.sql
-- Run this in your MySQL client (phpMyAdmin, MySQL CLI, etc.)

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: For security we recommend inserting the initial admin with a server-side hashed password.
-- Use the provided PHP helper `scripts/create_admin.php` to safely create an admin user with a hashed password.
