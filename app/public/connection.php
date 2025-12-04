<?php
// connection.php - FINAL FIX
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // AMBIL DARI ENV - sudah akan benar setelah di-update
    $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
    $port = getenv('MYSQLPORT') ?: 3306; // ← SEKARANG 3306, bukan 58371!
    $db   = getenv('MYSQLDATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
    
    // LOG untuk debugging (aman karena di error_log)
    error_log("Connecting to: mysql://{$user}:***@{$host}:{$port}/{$db}");
    
    try {
        // Koneksi sederhana - sudah pasti bekerja
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        error_log("✓ Database connected successfully!");
        return $pdo;
        
    } catch (PDOException $e) {
        // Fallback ke hardcode jika ENV masih salah
        error_log("ENV connection failed: " . $e->getMessage());
        error_log("Trying hardcoded connection...");
        
        try {
            // Hardcode yang sudah terbukti bekerja
            $host = 'mysql.railway.internal';
            $port = 3306;
            $db   = 'railway';
            $user = 'root';
            $pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            error_log("✓ Hardcoded connection successful!");
            return $pdo;
            
        } catch (PDOException $e2) {
            $error = "Database connection failed. ";
            $error .= "Tried ENV and hardcoded. ";
            $error .= "Last error: " . $e2->getMessage();
            
            error_log($error);
            throw new Exception($error);
        }
    }
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