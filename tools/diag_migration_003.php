<?php
require_once __DIR__ . '/../app/public/connection.php';

try {
    require_once __DIR__ . '/../database/migrations/003_update_files_table.php';
    $pdo = getDB();
    $migration = new UpdateFilesTable($pdo);
    $result = $migration->up();
    var_dump($result);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
