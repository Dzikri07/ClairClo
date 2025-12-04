<?php
// connection.php - FINAL SIMPLE VERSION
function getDB() {
    static $pdo = null;
    
    if ($pdo) return $pdo;
    
    // TRY MULTIPLE HOSTS
    $hosts = [
        'mysql.railway.internal',  // Railway internal
        '127.0.0.1',               // Localhost
        'localhost'                // Localhost alias
    ];
    
    $port = 58371;
    $db   = 'railway';
    $user = 'root';
    $pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
    
    $lastError = null;
    
    foreach ($hosts as $host) {
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            error_log("✓ Connected to $host:$port");
            return $pdo;
            
        } catch (PDOException $e) {
            $lastError = $e;
            error_log("✗ Failed to connect to $host:$port - " . $e->getMessage());
            continue; // Try next host
        }
    }
    
    // If all hosts failed
    throw new Exception("Cannot connect to MySQL: " . ($lastError ? $lastError->getMessage() : "Unknown error"));
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
?>