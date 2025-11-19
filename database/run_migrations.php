<?php
/**
 * Migration Runner - Executes all class-based migrations
 */

require_once __DIR__ . '/../app/public/connection.php';
require_once __DIR__ . '/../app/src/Migration.php';

try {
    $pdo = getDB();
    
    echo "Running migrations...\n\n";
    
    // Migration 001: Create users table
    require_once __DIR__ . '/migrations/001_create_users_table.php';
    $migration = new CreateUsersTable($pdo);
    $migration->up();
    echo "\n";
    
    // Migration 002: Create file categories table
    require_once __DIR__ . '/migrations/002_create_file_categories_table.php';
    $migration = new CreateFileCategoriesTable($pdo);
    $migration->up();
    echo "\n";
    
    // Migration 003: Update files table
    require_once __DIR__ . '/migrations/003_update_files_table.php';
    $migration = new UpdateFilesTable($pdo);
    $migration->up();
    echo "\n";
    
    // Migration 004: Seed file categories
    require_once __DIR__ . '/migrations/004_seed_file_categories.php';
    $migration = new SeedFileCategories($pdo);
    $migration->up();
    echo "\n";
    
    // Migration 005: Create storage requests table
    require_once __DIR__ . '/migrations/005_create_storage_requests_table.php';
    $migration = new CreateStorageRequestsTable($pdo);
    $migration->up();
    echo "\n";
    
    // Migration 006: Create file storage paths table
    require_once __DIR__ . '/migrations/006_create_file_storage_paths_table.php';
    $migration = new CreateFileStoragePathsTable($pdo);
    $migration->up();
    echo "\n";
    
    echo "✓ All migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
