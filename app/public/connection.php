<?php
/**
 * connection.php - SIMPLE Database connection for Railway
 * VERSION SANGAT SEDERHANA tanpa constant yang bermasalah
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// FUNGSI SANGAT SEDERHANA - langsung konek tanpa class kompleks
function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Config langsung dari ENV
    $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
    $port = getenv('MYSQLPORT') ?: '58371';
    $db   = getenv('MYSQLDATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
    
    // Debug
    error_log("=== DB CONFIG ===");
    error_log("Host: $host");
    error_log("Port: $port");
    error_log("DB: $db");
    error_log("User: $user");
    
    try {
        // DSN sederhana
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        
        // Coba koneksi basic
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        error_log("✓ Database connected!");
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("✗ Connection failed: " . $e->getMessage());
        
        // Coba tanpa database dulu
        try {
            error_log("Trying without database...");
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            error_log("✓ Connected without database");
            return $pdo;
            
        } catch (PDOException $e2) {
            error_log("✗ Fallback also failed: " . $e2->getMessage());
            throw new Exception("Database connection failed. Check Railway MySQL service.");
        }
    }
}

// Helper functions sederhana
function db_query($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch_all($sql, $params = []) {
    return db_query($sql, $params)->fetchAll();
}

function db_fetch_one($sql, $params = []) {
    return db_query($sql, $params)->fetch();
}

function db_insert($table, $data) {
    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
    db_query($sql, array_values($data));
    return getDB()->lastInsertId();
}

function db_update($table, $data, $where, $whereParams = []) {
    $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
    $sql = "UPDATE $table SET $set WHERE $where";
    $params = array_merge(array_values($data), $whereParams);
    return db_query($sql, $params)->rowCount();
}

function db_delete($table, $where, $whereParams = []) {
    $sql = "DELETE FROM $table WHERE $where";
    return db_query($sql, $whereParams)->rowCount();
}

// Alias untuk compatibility dengan kode lama
function query($sql, $params = []) { return db_query($sql, $params); }
function fetchAll($sql, $params = []) { return db_fetch_all($sql, $params); }
function fetchOne($sql, $params = []) { return db_fetch_one($sql, $params); }
function insert($table, $data) { return db_insert($table, $data); }
function update($table, $data, $where, $whereParams = []) { return db_update($table, $data, $where, $whereParams); }
function delete($table, $where, $whereParams = []) { return db_delete($table, $where, $whereParams); }
