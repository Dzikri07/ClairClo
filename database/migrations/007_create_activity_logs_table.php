<?php
/**
 * Migration 007: Create activity_logs table
 * 
 * Creates a table to store admin activity logs for audit trail
 */

// Get database connection
require_once __DIR__ . '/../../app/public/connection.php';

try {
    $pdo = getDB();
    
    // Create activity_logs table
    $sql = "
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
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✓ Migration 007: activity_logs table created successfully.\n";
    
} catch (PDOException $e) {
    echo "✗ Migration 007 failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
