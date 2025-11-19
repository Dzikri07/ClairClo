<?php
require_once __DIR__ . '/../app/public/connection.php';

try {
    $pdo = getDB();
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new Exception('Could not read schema.sql');
    }

    // Split statements by semicolon followed by newline (simple approach)
    $statements = preg_split('/;\s*\n/', $schema);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        // Skip comments
        if (strpos($stmt, '--') === 0 || strpos($stmt, '/*') === 0) continue;

        try {
            $pdo->exec($stmt);
            echo "Executed statement.\n";
        } catch (PDOException $e) {
            echo "Statement failed: " . $e->getMessage() . "\n";
            echo "SQL: " . substr($stmt, 0, 200) . "...\n";
        }
    }

    echo "Schema apply completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
