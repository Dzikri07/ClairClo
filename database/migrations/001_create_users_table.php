<?php
/**
 * Migration: Create users table
 * Description: Creates the users table for user authentication and management
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->log("Creating users table...");

        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(100) DEFAULT NULL,
            `storage_quota` BIGINT UNSIGNED DEFAULT 5368709120 COMMENT 'Storage quota in bytes (default 5GB)',
            `storage_used` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Storage used in bytes',
            `avatar` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
            `last_login` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_username` (`username`),
            INDEX `idx_email` (`email`),
            INDEX `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User accounts table'";

        if ($this->execute($sql)) {
            $this->log("Users table created successfully");
            
            // Create default admin user
            $this->createDefaultAdmin();
            
            return true;
        }

        return false;
    }

    public function down()
    {
        $this->log("Dropping users table...");
        
        if ($this->dropTable('users')) {
            $this->log("Users table dropped successfully");
            return true;
        }

        return false;
    }

    /**
     * Create default admin user
     */
    private function createDefaultAdmin()
    {
        $this->log("Creating default admin user...");

        // Check if admin already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        
        if ($stmt->fetchColumn() > 0) {
            $this->log("Admin user already exists, skipping...");
            return;
        }

        // Create admin user (password: admin123)
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, full_name, is_admin, storage_quota) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'admin',
            'admin@clariocloud.local',
            $hashedPassword,
            'Administrator',
            1,
            107374182400 // 100GB for admin
        ]);

        $this->log("Default admin user created (username: admin, password: admin123)");
    }
}
