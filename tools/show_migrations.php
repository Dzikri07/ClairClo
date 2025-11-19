<?php
require_once __DIR__ . '/../app/public/connection.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT migration, batch, executed_at FROM migrations ORDER BY id');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Migrations table:\n";
    foreach ($rows as $r) {
        echo " - {$r['migration']} (batch {$r['batch']}) executed_at={$r['executed_at']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
