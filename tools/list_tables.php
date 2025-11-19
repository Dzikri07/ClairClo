<?php
require_once __DIR__ . '/../app/public/connection.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query('SHOW TABLES');
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($rows as $r) {
        echo " - " . $r . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
