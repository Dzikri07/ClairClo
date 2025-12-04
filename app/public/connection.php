<?php
/**
 * connection.php - Database connection handler for Railway
 * SIMPLIFIED VERSION dengan error handling yang lebih baik
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CEK EXTENSI DULU SEBELUM APA-APA
function checkAndLoadExtensions()
{
    error_log("=== CHECKING PHP EXTENSIONS ===");
    
    // List extensions yang diperlukan
    $required = ['pdo', 'pdo_mysql'];
    $optional = ['mysqli', 'mbstring', 'json', 'openssl'];
    
    $allExtensions = get_loaded_extensions();
    error_log("Loaded extensions: " . implode(', ', $allExtensions));
    
    // Cek yang required
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            error_log("CRITICAL ERROR: Extension '$ext' not loaded!");
            error_log("Suggestion for Railway: Add to nixpacks.toml:");
            error_log('[phases.setup]');
            error_log('aptPkgs = ["php8.2-mysql", "php8.2-pdo"]');
            
            throw new Exception(
                "PHP extension '$ext' is not installed. " .
                "Please add 'php8.2-mysql' and 'php8.2-pdo' to your Railway configuration."
            );
        } else {
            error_log("✓ Extension '$ext' is loaded");
        }
    }
    
    // Cek PDO drivers
    if (extension_loaded('pdo')) {
        $drivers = PDO::getAvailableDrivers();
        error_log("PDO Drivers available: " . implode(', ', $drivers));
        
        if (!in_array('mysql', $drivers)) {
            throw new Exception("PDO MySQL driver not available. Install 'php8.2-pdo-mysql' package.");
        }
    }
}

// Jalankan check
checkAndLoadExtensions();

class DatabaseConnection
{
    private static $instance = null;
    private $pdo = null;

    private function __construct()
    {
        // Konfigurasi sederhana langsung dari ENV
        $this->host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
        $this->port = getenv('MYSQLPORT') ?: '58371';
        $this->db   = getenv('MYSQLDATABASE') ?: 'railway';
        $this->user = getenv('MYSQLUSER') ?: 'root';
        $this->pass = getenv('MYSQLPASSWORD') ?: 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
        
        error_log("=== DATABASE CONNECTION ===");
        error_log("Host: {$this->host}");
        error_log("Port: {$this->port}");
        error_log("Database: {$this->db}");
        error_log("User: {$this->user}");
        
        $this->connect();
    }

    private function connect()
    {
        try {
            // DSN sederhana
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";
            error_log("Connecting with DSN: mysql://{$this->user}:***@{$this->host}:{$this->port}/{$this->db}");
            
            // Options minimal - hindari constant yang mungkin tidak ada
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];
            
            // Coba tambahkan SSL options jika constant tersedia
            if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            
            // Set charset manual jika constant tidak tersedia
            $this->pdo->exec("SET NAMES utf8mb4");
            $this->pdo->exec("SET time_zone = '+00:00'");
            
            error_log("✓ Database connected successfully!");
            
        } catch (PDOException $e) {
            error_log("✗ Database connection failed: " . $e->getMessage());
            
            // Coba tanpa database dulu
            error_log("Trying without database name...");
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
                $tempPdo = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Cek database
                $stmt = $tempPdo->query("SHOW DATABASES LIKE '{$this->db}'");
                if ($stmt->fetch()) {
                    error_log("Database '{$this->db}' exists, reconnecting...");
                    $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";
                    $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
                } else {
                    error_log("Database '{$this->db}' not found, using connection without database");
                    $this->pdo = $tempPdo;
                    $this->db = '';
                }
                
            } catch (PDOException $e2) {
                error_log("Fallback connection also failed: " . $e2->getMessage());
                throw new Exception("Could not connect to database. Please check Railway MySQL service.");
            }
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

// Helper functions tetap sama
function query($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function fetchOne($sql, $params = []) {
    return query($sql, $params)->fetch();
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

// Simple debug function
function debugDB() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT 1 as test, DATABASE() as db, USER() as user, VERSION() as version");
        return $stmt->fetch();
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
