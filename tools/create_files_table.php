<?php
require_once __DIR__ . '/../app/public/connection.php';
try {
    $pdo = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS `files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) DEFAULT NULL,
  `size` BIGINT UNSIGNED DEFAULT 0,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
  `is_trashed` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`filename`),
  INDEX (`is_favorite`),
  INDEX (`is_trashed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);
    echo "files table created or already exists\n";
} catch (Exception $e) {
    echo "Error creating files table: " . $e->getMessage() . "\n";
}
