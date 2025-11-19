<?php
require_once __DIR__ . '/../app/public/connection.php';
try {
    $pdo = getDB();
    $check = ['users','files','file_categories','storage_requests','migrations'];
    foreach ($check as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '" . $t . "'");
        $exists = $stmt->rowCount() > 0 ? 'YES' : 'NO';
        echo "$t: $exists\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
