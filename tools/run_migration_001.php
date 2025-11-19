<?php
require_once __DIR__ . '/../app/public/connection.php';
try {
    require_once __DIR__ . '/../database/migrations/001_create_users_table.php';
    $pdo = getDB();
    $migration = new CreateUsersTable($pdo);
    $res = $migration->up();
    echo "CreateUsersTable up returned: "; var_export($res); echo PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
