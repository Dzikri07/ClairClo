-- ClarioCloud database schema
-- Run this in MySQL to create the files table

CREATE DATABASE IF NOT EXISTS clariocloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clariocloud;

-- files table stores uploaded files metadata
CREATE TABLE IF NOT EXISTS files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  filename VARCHAR(255) NOT NULL, -- stored filename on disk
  original_name VARCHAR(255) NOT NULL, -- original uploaded name
  mime VARCHAR(100) DEFAULT NULL,
  size BIGINT UNSIGNED DEFAULT 0,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_favorite TINYINT(1) NOT NULL DEFAULT 0,
  is_trashed TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX (filename),
  INDEX (is_favorite),
  INDEX (is_trashed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- activity_logs table stores admin activity audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45),
  PRIMARY KEY (id),
  INDEX (admin_id),
  INDEX (action),
  INDEX (created_at),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
