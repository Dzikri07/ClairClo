<?php
// connection.php - FINAL FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // DEBUG: Tampilkan semua options
    echo "<!-- DB Connection Debug -->";
    
    // PILIHAN URUTAN (prioritas):
    $connection_options = [];
    
    // 1. Coba MYSQL_URL dulu (internal - lebih cepat)
    if ($url = getenv('MYSQL_URL')) {
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $connection_options[] = [
                'name' => 'MYSQL_URL',
                'host' => $parsed['host'],
                'port' => $parsed['port'] ?? 58371,
                'db'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway',
                'user' => $parsed['user'] ?? 'root',
                'pass' => $parsed['pass'] ?? 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
            ];
        }
    }
    
    // 2. Coba individual variables
    $connection_options[] = [
        'name' => 'Individual ENV',
        'host' => getenv('MYSQLHOST') ?: 'mysql.railway.internal',
        'port' => getenv('MYSQLPORT') ?: 58371,
        'db'   => getenv('MYSQLDATABASE') ?: 'railway',
        'user' => getenv('MYSQLUSER') ?: 'root',
        'pass' => getenv('MYSQLPASSWORD') ?: 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    // 3. Coba MYSQL_PUBLIC_URL (external)
    if ($url = getenv('MYSQL_PUBLIC_URL')) {
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $connection_options[] = [
                'name' => 'MYSQL_PUBLIC_URL',
                'host' => $parsed['host'],
                'port' => $parsed['port'] ?? 58371,
                'db'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway',
                'user' => $parsed['user'] ?? 'root',
                'pass' => $parsed['pass'] ?? 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
            ];
        }
    }
    
    // 4. Hardcode fallback (pakai yang dari gambar)
    $connection_options[] = [
        'name' => 'Hardcode 1 (Internal)',
        'host' => 'mysql.railway.internal',
        'port' => 58371,
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    $connection_options[] = [
        'name' => 'Hardcode 2 (External)',
        'host' => 'ballast.proxy.rlwy.net',
        'port' => 58371,
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    $last_error = null;
    
    // COBA SEMUA OPTION
    foreach ($connection_options as $option) {
        echo "<!-- Trying: {$option['name']} -->";
        echo "<!-- Host: {$option['host']}:{$option['port']} -->";
        
        try {
            $dsn = "mysql:host={$option['host']};port={$option['port']};dbname={$option['db']};charset=utf8mb4";
            
            $pdo = new PDO($dsn, $option['user'], $option['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Test connection
            $pdo->query("SELECT 1");
            
            echo "<!-- ✓ Connected via {$option['name']} -->";
            error_log("Database connected via {$option['name']}: {$option['host']}:{$option['port']}");
            
            return $pdo;
            
        } catch (PDOException $e) {
            $last_error = $e;
            echo "<!-- ✗ {$option['name']} failed: " . $e->getMessage() . " -->";
            error_log("Connection failed ({$option['name']}): " . $e->getMessage());
            continue;
        }
    }
    
    // Jika semua gagal
    throw new Exception(
        "All database connection attempts failed. " .
        "Last error: " . ($last_error ? $last_error->getMessage() : "Unknown") . ". " .
        "Please check Railway MySQL service and port configuration (should be 58371)."
    );
}

// Helper functions...
function query($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
}

function fetchOne($sql, $params = []) {
    return query($sql, $params)->fetch(PDO::FETCH_ASSOC);
}

function insert($table, $data) {
    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    
    $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
    query($sql, array_values($data));
    
    return getDB()->lastInsertId();
}

function update($table, $data, $where, $whereParams = []) {
    $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
    $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
    $params = array_merge(array_values($data), $whereParams);
    
    return query($sql, $params)->rowCount();
}

function delete($table, $where, $whereParams = []) {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    return query($sql, $whereParams)->rowCount();
}