<?php
/**
 * connection.php - Database connection handler
 * Provides a singleton PDO connection for the application
 */

class DatabaseConnection
{
    private static $instance = null;
    private $pdo = null;

    // Database configuration
    private $host = '127.0.0.1';
    private $db = 'clariocloud';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';

    private function __construct()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
            ];
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Could not connect to database. Please check your configuration.");
        }
    }

    /**
     * Get singleton instance of database connection
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 * @return PDO
 */
function getDB()
{
    return DatabaseConnection::getInstance()->getConnection();
}

/**
 * Helper function to execute a query
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function query($sql, $params = [])
{
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Helper function to fetch all rows
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = [])
{
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Helper function to fetch single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = [])
{
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

/**
 * Helper function to insert data
 * @param string $table
 * @param array $data
 * @return string Last insert ID
 */
function insert($table, $data)
{
    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    
    $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
    $stmt = query($sql, array_values($data));
    
    return getDB()->lastInsertId();
}

/**
 * Helper function to update data
 * @param string $table
 * @param array $data
 * @param string $where
 * @param array $whereParams
 * @return int Number of affected rows
 */
function update($table, $data, $where, $whereParams = [])
{
    $setParts = [];
    foreach (array_keys($data) as $key) {
        $setParts[] = "{$key} = ?";
    }
    $setClause = implode(', ', $setParts);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    $params = array_merge(array_values($data), $whereParams);
    $stmt = query($sql, $params);
    
    return $stmt->rowCount();
}

/**
 * Helper function to delete data
 * @param string $table
 * @param string $where
 * @param array $whereParams
 * @return int Number of affected rows
 */
function delete($table, $where, $whereParams = [])
{
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = query($sql, $whereParams);
    return $stmt->rowCount();
}

/**
 * Log an activity to the activity_logs table
 * @param string $action
 * @param string $description
 * @param int $admin_id (optional, defaults to current user)
 * @return string|false Last insert ID or false if failed
 */
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
