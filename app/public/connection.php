<?php
/**
 * connection.php - Database connection handler for Railway
 */

class DatabaseConnection
{
    private static $instance = null;
    private $pdo = null;

    // Load config from Railway ENV
    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
    private $charset = 'utf8mb4';

    private function __construct()
    {
        // Ambil variabel dari Railway
        $this->host = $_ENV['MYSQLHOST'] ?? '127.0.0.1';
        $this->db   = $_ENV['MYSQLDATABASE'] ?? 'clariocloud';
        $this->user = $_ENV['MYSQLUSER'] ?? 'root';
        $this->pass = $_ENV['MYSQLPASSWORD'] ?? '';
        $this->port = $_ENV['MYSQLPORT'] ?? 3306;

        try {
            // DSN khusus Railway (harus pakai port)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];

            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);

        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Could not connect to database. Check Railway ENV settings.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}

function getDB()
{
    return DatabaseConnection::getInstance()->getConnection();
}

function query($sql, $params = [])
{
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchAll($sql, $params = [])
{
    return query($sql, $params)->fetchAll();
}

function fetchOne($sql, $params = [])
{
    return query($sql, $params)->fetch();
}

function insert($table, $data)
{
    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));

    $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
    query($sql, array_values($data));

    return getDB()->lastInsertId();
}

function update($table, $data, $where, $whereParams = [])
{
    $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));

    $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
    $params = array_merge(array_values($data), $whereParams);

    return query($sql, $params)->rowCount();
}

function delete($table, $where, $whereParams = [])
{
    $sql = "DELETE FROM {$table} WHERE {$where}";
    return query($sql, $whereParams)->rowCount();
}

function log_activity($action, $description = '', $admin_id = null)
{
    try {
        if ($admin_id === null && isset($_SESSION['id'])) {
            $admin_id = $_SESSION['id'];
        }

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

        $data = [
            'action' => $action,
            'description' => $description,
            'admin_id' => $admin_id,
            'ip_address' => $ip_address
        ];

        return insert('activity_logs', $data);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}
