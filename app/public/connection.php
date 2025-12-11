<?php
// connection.php - AUTO LOCAL / RAILWAY SWITCH
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- KONFIG LOKAL (untuk debug offline) ---
$LOCAL_DB = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'db'   => 'clariocloud',
    'user' => 'root',
    'pass' => '',
];

function getDB() {
    static $pdo = null;
    global $LOCAL_DB;

    if ($pdo !== null) {
        return $pdo;
    }

    // Railway selalu punya ENV MYSQLHOST / MYSQLUSER
    $isRailway = getenv('MYSQLHOST') && getenv('MYSQLUSER');

    if ($isRailway) {
        // ------------------ MODE RAILWAY ------------------
        $host = getenv('MYSQLHOST');
        $port = getenv('MYSQLPORT') ?: 3306;
        $db   = getenv('MYSQLDATABASE');
        $user = getenv('MYSQLUSER');
        $pass = getenv('MYSQLPASSWORD');

        error_log("MODE: Railway – connecting to Railway DB");

    } else {
        // ------------------- MODE LOCAL -------------------
        $host = $LOCAL_DB['host'];
        $port = $LOCAL_DB['port'];
        $db   = $LOCAL_DB['db'];
        $user = $LOCAL_DB['user'];
        $pass = $LOCAL_DB['pass'];

        error_log("MODE: LOCAL – connecting to local MySQL");
    }

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        error_log("✓ Database connected successfully!");
        return $pdo;

    } catch (PDOException $e) {
        error_log("✗ Database connection failed: " . $e->getMessage());
        throw $e;
    }
}

// =====================
// Helper Functions
// =====================

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

/**
 * Log an activity to the activity_logs table.
 * Parameters are defensive — function will not throw on failure.
 */
function log_activity($action, $description = null, $admin_id = null, $ip_address = null) {
    try {
        if ($admin_id === null) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $admin_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        }

        if ($ip_address === null) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        $data = [
            'admin_id' => $admin_id,
            'action' => substr((string)$action, 0, 191),
            'description' => $description,
            'ip_address' => $ip_address,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Use insert helper if table exists
        insert('activity_logs', $data);
        return true;
    } catch (Exception $e) {
        error_log('log_activity error: ' . $e->getMessage());
        return false;
    }
}
