<?php
// test-pdo.php - Test PDO langsung tanpa class
echo "<h2>PDO Direct Test</h2>";

// Config
$host = 'mysql.railway.internal';
$port = 58371;
$db = 'railway';
$user = 'root';
$pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';

echo "<pre>";
echo "Config:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "DB: $db\n";
echo "User: $user\n";
echo "Pass: " . substr($pass, 0, 4) . "****\n";
echo "</pre>";

// Cek PDO extension
echo "<h3>Checking PDO...</h3>";
if (!extension_loaded('pdo')) {
    die("<p style='color: red;'>✗ PDO extension not loaded!</p>");
}
echo "<p style='color: green;'>✓ PDO extension loaded</p>";

// Cek PDO drivers
$drivers = PDO::getAvailableDrivers();
echo "<h3>PDO Drivers:</h3>";
echo "<pre>";
print_r($drivers);
echo "</pre>";

if (!in_array('mysql', $drivers)) {
    die("<p style='color: red;'>✗ PDO MySQL driver not available!</p>");
}

// Coba koneksi
echo "<h3>Testing connection...</h3>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db";
    echo "DSN: $dsn<br>";
    
    $pdo = new PDO($dsn, $user, $pass);
    echo "<p style='color: green;'>✓ PDO object created successfully!</p>";
    
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>✓ Query executed: " . $result['test'] . "</p>";
    
    // Show database info
    $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user, VERSION() as version");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Database Info:</h3>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ PDO Error: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    
    // Coba tanpa database
    echo "<h3>Trying without database...</h3>";
    try {
        $dsn = "mysql:host=$host;port=$port";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color: green;'>✓ Connected without database!</p>";
        
        // List databases
        $stmt = $pdo->query("SHOW DATABASES");
        $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<h4>Available databases:</h4>";
        echo "<ul>";
        foreach ($dbs as $database) {
            echo "<li>$database" . ($database == $db ? " ← TRYING TO CONNECT" : "") . "</li>";
        }
        echo "</ul>";
        
    } catch (PDOException $e2) {
        echo "<p style='color: red;'>✗ Also failed: " . $e2->getMessage() . "</p>";
    }
}

// Cek PHP info tentang PDO
echo "<h3>PDO Configuration:</h3>";
$pdoConfig = [
    'PDO::ATTR_ERRMODE' => defined('PDO::ATTR_ERRMODE') ? 'DEFINED' : 'UNDEFINED',
    'PDO::MYSQL_ATTR_SSL_CA' => defined('PDO::MYSQL_ATTR_SSL_CA') ? 'DEFINED' : 'UNDEFINED',
    'PDO::MYSQL_ATTR_INIT_COMMAND' => defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? 'DEFINED' : 'UNDEFINED',
];
echo "<pre>";
print_r($pdoConfig);
echo "</pre>";
?>
