<?php
/**
 * connection.php - Database connection handler for Railway
 * Enhanced version with comprehensive error handling and debugging
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DatabaseConnection
{
    private static $instance = null;
    private $pdo = null;
    private $connectionDetails = [];

    private function __construct()
    {
        $this->initializeConnection();
        $this->establishConnection();
    }

    private function initializeConnection()
    {
        // ANALISIS: Railway memiliki dua tipe variabel environment:
        // 1. Service Variables (untuk service MySQL)
        // 2. Project Variables (untuk aplikasi PHP)
        // Keduanya perlu ditangani dengan baik
        
        // DEBUG: Log semua environment variables yang relevan
        $this->logEnvironment();
        
        // STRATEGI: Coba semua sumber konfigurasi yang mungkin
        $this->determineConnectionDetails();
    }

    private function logEnvironment()
    {
        error_log("=== DATABASE CONNECTION INITIALIZATION ===");
        
        $relevantVars = [
            'MYSQLHOST', 'MYSQLPORT', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD',
            'MYSQL_DATABASE_URL', 'DATABASE_URL', 'RAILWAY_ENVIRONMENT',
            'RAILWAY_SERVICE_NAME', 'RAILWAY_PROJECT_NAME'
        ];
        
        foreach ($relevantVars as $var) {
            $value = getenv($var);
            if ($value) {
                error_log("$var = " . (strpos($var, 'PASSWORD') !== false ? substr($value, 0, 4) . '****' : $value));
            }
        }
        
        error_log("=== END ENVIRONMENT LOG ===");
    }

    private function determineConnectionDetails()
    {
        // PRIORITAS 1: Gunakan MYSQL_DATABASE_URL jika tersedia (format lengkap)
        $databaseUrl = getenv('MYSQL_DATABASE_URL');
        if ($databaseUrl) {
            $this->parseDatabaseUrl($databaseUrl);
            error_log("Using MYSQL_DATABASE_URL configuration");
            return;
        }
        
        // PRIORITAS 2: Gunakan individual environment variables
        $this->connectionDetails = [
            'host' => getenv('MYSQLHOST') ?: 'mysql.railway.internal',
            'port' => $this->getPort(), // Fungsi khusus untuk handling port
            'dbname' => getenv('MYSQLDATABASE') ?: 'railway',
            'username' => getenv('MYSQLUSER') ?: 'root',
            'password' => getenv('MYSQLPASSWORD') ?: '',
            'charset' => 'utf8mb4'
        ];
        
        error_log("Using individual ENV variables configuration");
    }

    private function getPort()
    {
        // ANALISIS: Port adalah masalah utama berdasarkan diskusi sebelumnya
        // Railway menggunakan port dinamis (58371), bukan standar 3306
        
        $port = getenv('MYSQLPORT');
        
        if (!$port) {
            error_log("WARNING: MYSQLPORT not set in environment");
            
            // Coba ekstrak dari MYSQL_DATABASE_URL jika ada
            $dbUrl = getenv('MYSQL_DATABASE_URL');
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                if (isset($parsed['port'])) {
                    error_log("Extracted port {$parsed['port']} from MYSQL_DATABASE_URL");
                    return $parsed['port'];
                }
            }
            
            // Fallback berdasarkan analisis error sebelumnya
            error_log("Using fallback port 58371 based on previous analysis");
            return 58371;
        }
        
        // Validasi port
        $port = (int)$port;
        if ($port < 1 || $port > 65535) {
            error_log("ERROR: Invalid port {$port}. Using default 58371");
            return 58371;
        }
        
        error_log("Using port from ENV: {$port}");
        return $port;
    }

    private function parseDatabaseUrl($url)
    {
        $parsed = parse_url($url);
        
        $this->connectionDetails = [
            'host' => $parsed['host'] ?? 'mysql.railway.internal',
            'port' => $parsed['port'] ?? $this->getPort(),
            'dbname' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway',
            'username' => $parsed['user'] ?? 'root',
            'password' => $parsed['pass'] ?? '',
            'charset' => 'utf8mb4'
        ];
        
        error_log("Parsed database URL: mysql://{$this->connectionDetails['username']}:***@{$this->connectionDetails['host']}:{$this->connectionDetails['port']}/{$this->connectionDetails['dbname']}");
    }

    private function establishConnection()
    {
        $maxAttempts = 3;
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $maxAttempts) {
            $attempt++;
            error_log("Connection attempt {$attempt}/{$maxAttempts}");
            
            try {
                $this->createPDOConnection();
                $this->testConnection();
                error_log("✓ Database connection established successfully");
                return;
                
            } catch (PDOException $e) {
                $lastError = $e;
                error_log("Connection attempt {$attempt} failed: " . $e->getMessage());
                
                // ANALISIS: Railway terkadang membutuhkan penanganan khusus:
                // 1. SSL/TLS connection
                // 2. Timeout handling
                // 3. Connection pooling
                
                if ($attempt < $maxAttempts) {
                    // Tunggu sebelum retry
                    sleep(1);
                    $this->adjustConnectionStrategy($attempt);
                }
            } catch (Exception $e) {
                $lastError = $e;
                error_log("General error in connection attempt {$attempt}: " . $e->getMessage());
                break;
            }
        }
        
        // Jika semua percobaan gagal
        $this->handleConnectionFailure($lastError);
    }

    private function createPDOConnection()
    {
        $host = $this->connectionDetails['host'];
        $port = $this->connectionDetails['port'];
        $dbname = $this->connectionDetails['dbname'];
        $username = $this->connectionDetails['username'];
        $password = $this->connectionDetails['password'];
        $charset = $this->connectionDetails['charset'];
        
        // DSN (Data Source Name)
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        error_log("Creating PDO connection with DSN: mysql://{$username}:***@{$host}:{$port}/{$dbname}");
        
        // OPTIMASI: Konfigurasi PDO untuk performa dan keamanan
        $options = [
            // Error handling
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // Performance optimizations
            PDO::ATTR_EMULATE_PREPARES   => false, // Gunakan prepared statements native
            PDO::ATTR_PERSISTENT         => false, // Non-persistent connections untuk Railway
            
            // Timeout settings
            PDO::ATTR_TIMEOUT            => 10,
            
            // Character set
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
            
            // ANALISIS: Railway sering membutuhkan SSL
            PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            
            // Connection limits
            PDO::MYSQL_ATTR_MAX_BUFFER_SIZE => 1024 * 1024 * 100, // 100MB
        ];
        
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }

    private function testConnection()
    {
        // Test koneksi dengan query sederhana
        $stmt = $this->pdo->query("SELECT 1 as connection_test");
        $result = $stmt->fetch();
        
        if (!$result || $result['connection_test'] != 1) {
            throw new Exception("Connection test failed");
        }
        
        // Test akses ke database
        $stmt = $this->pdo->query("SELECT DATABASE() as current_db");
        $db = $stmt->fetch();
        error_log("Connected to database: " . ($db['current_db'] ?? 'Unknown'));
    }

    private function adjustConnectionStrategy($attempt)
    {
        // ANALISIS: Sesuaikan strategi berdasarkan attempt yang gagal
        
        switch ($attempt) {
            case 1:
                // Percobaan pertama gagal, coba tanpa SSL
                error_log("Attempt 1 failed, trying without SSL...");
                $this->connectionDetails['ssl'] = false;
                break;
                
            case 2:
                // Percobaan kedua gagal, coba koneksi tanpa nama database
                error_log("Attempt 2 failed, trying without database name...");
                $this->connectionDetails['dbname'] = '';
                break;
        }
    }

    private function handleConnectionFailure($lastError)
    {
        error_log("=== DATABASE CONNECTION FAILURE ANALYSIS ===");
        error_log("All connection attempts failed");
        error_log("Last error: " . $lastError->getMessage());
        error_log("Connection details used:");
        error_log(print_r($this->connectionDetails, true));
        
        // Saran troubleshooting berdasarkan analisis error
        $troubleshooting = [
            "1. Check Railway dashboard for MySQL service status",
            "2. Verify environment variables in Project Settings",
            "3. Ensure MySQL service is connected to your application",
            "4. Check if database '{$this->connectionDetails['dbname']}' exists",
            "5. Verify user '{$this->connectionDetails['username']}' has proper permissions",
            "6. Test connection with different ports (58371, 3306)",
            "7. Check network connectivity between services in Railway"
        ];
        
        error_log("Troubleshooting steps:");
        foreach ($troubleshooting as $step) {
            error_log("  - " . $step);
        }
        
        throw new Exception(
            "Database connection failed after multiple attempts. " .
            "Last error: " . $lastError->getMessage() . ". " .
            "Check Railway logs for detailed troubleshooting."
        );
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
        if (!$this->pdo) {
            throw new Exception("Database connection not initialized");
        }
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

// Helper function untuk debugging koneksi
function debugDatabaseConnection()
{
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT 
            CONNECTION_ID() as connection_id,
            DATABASE() as current_database,
            USER() as current_user,
            VERSION() as mysql_version");
        
        $info = $stmt->fetch();
        
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Database Connection Info</h4>";
        echo "<pre>";
        print_r($info);
        echo "</pre>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Database Connection Error</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}
