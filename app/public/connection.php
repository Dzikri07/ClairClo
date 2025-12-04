<?php
// connection.php - CORRECT PORT VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // DEBUG
    echo "<!-- DB Connection Initialized -->";
    
    // OPTIMIZED ORDER BASED ON TEST RESULTS:
    $connection_options = [];
    
    // 1. MYSQL_URL dengan port 3306 (INTERNAL - terbukti bekerja)
    if ($url = getenv('MYSQL_URL')) {
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $connection_options[] = [
                'name' => 'MYSQL_URL',
                'host' => $parsed['host'],
                'port' => $parsed['port'] ?? 3306, // ← 3306, bukan 58371!
                'db'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway',
                'user' => $parsed['user'] ?? 'root',
                'pass' => $parsed['pass'] ?? 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
            ];
        }
    }
    
    // 2. MYSQL_PUBLIC_URL dengan port 50371 (EXTERNAL - terbukti bekerja)
    if ($url = getenv('MYSQL_PUBLIC_URL')) {
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $connection_options[] = [
                'name' => 'MYSQL_PUBLIC_URL',
                'host' => $parsed['host'],
                'port' => $parsed['port'] ?? 50371, // ← 50371, bukan 58371!
                'db'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway',
                'user' => $parsed['user'] ?? 'root',
                'pass' => $parsed['pass'] ?? 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
            ];
        }
    }
    
    // 3. Hardcode berdasarkan test yang sukses
    $connection_options[] = [
        'name' => 'Hardcode Internal',
        'host' => 'mysql.railway.internal',
        'port' => 3306, // ← 3306 BERHASIL!
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    $connection_options[] = [
        'name' => 'Hardcode External',
        'host' => 'ballast.proxy.rlwy.net',
        'port' => 50371, // ← 50371 BERHASIL!
        'db'   => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    // 4. Individual ENV vars (fallback)
    $connection_options[] = [
        'name' => 'ENV Variables',
        'host' => getenv('MYSQLHOST') ?: 'mysql.railway.internal',
        'port' => (int) (getenv('MYSQLPORT') ?: 3306), // ← Default 3306
        'db'   => getenv('MYSQLDATABASE') ?: 'railway',
        'user' => getenv('MYSQLUSER') ?: 'root',
        'pass' => getenv('MYSQLPASSWORD') ?: 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ];
    
    $last_error = null;
    
    foreach ($connection_options as $option) {
        echo "<!-- Trying {$option['name']}: {$option['host']}:{$option['port']} -->";
        
        try {
            $dsn = "mysql:host={$option['host']};port={$option['port']};dbname={$option['db']};charset=utf8mb4";
            
            $pdo = new PDO($dsn, $option['user'], $option['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Test connection
            $stmt = $pdo->query("SELECT 1 as connected, DATABASE() as db");
            $result = $stmt->fetch();
            
            echo "<!-- ✓ Connected via {$option['name']} to {$result['db']} -->";
            error_log("Database connected via {$option['name']}: {$option['host']}:{$option['port']}");
            
            return $pdo;
            
        } catch (PDOException $e) {
            $last_error = $e;
            echo "<!-- ✗ {$option['name']} failed: " . $e->getMessage() . " -->";
            continue;
        }
    }
    
    throw new Exception(
        "Database connection failed. " .
        "Tried multiple hosts/ports. " .
        "Last error: " . ($last_error ? $last_error->getMessage() : "Unknown")
    );
}

// Helper functions tetap sama...
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