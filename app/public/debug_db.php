<?php
// debug_db.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Database Connection Debug</h3>";

$host = 'mysql.railway.internal';
$port = 58371;
$db = 'railway';
$user = 'root';
$pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';

echo "<pre>";
echo "Connection details:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $db\n";
echo "User: $user\n";
echo "Password: " . substr($pass, 0, 4) . "****\n";
echo "</pre>";

// Test 1: Coba koneksi PDO
echo "<h4>Test 1: PDO Connection</h4>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<div style='color: green;'>✓ PDO Connected successfully!</div>";
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<div style='color: green;'>✓ Query test successful: " . $result['test'] . "</div>";
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div>Tables found: " . count($tables) . "</div>";
    if ($tables) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ PDO Error: " . $e->getMessage() . "</div>";
    echo "<div>Error Code: " . $e->getCode() . "</div>";
}

// Test 2: Coba tanpa database name
echo "<h4>Test 2: Connect without database</h4>";
try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo2 = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<div style='color: green;'>✓ Connected without database</div>";
    
    // List databases
    $stmt = $pdo2->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div>Databases found: " . count($databases) . "</div>";
    echo "<div>Looking for 'railway': " . (in_array('railway', $databases) ? '✓ FOUND' : '✗ NOT FOUND') . "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ Error: " . $e->getMessage() . "</div>";
}

// Test 3: Coba dengan mysqli
echo "<h4>Test 3: mysqli Connection</h4>";
try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    
    if ($mysqli->connect_error) {
        echo "<div style='color: red;'>✗ mysqli Error: " . $mysqli->connect_error . "</div>";
    } else {
        echo "<div style='color: green;'>✓ mysqli Connected successfully!</div>";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ mysqli Exception: " . $e->getMessage() . "</div>";
}

// Test 4: Cek network connection dengan fsockopen
echo "<h4>Test 4: Network Connection Test</h4>";
$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, 5);
$end = microtime(true);

if ($fp) {
    echo "<div style='color: green;'>✓ Network connection successful (" . round(($end - $start) * 1000, 2) . " ms)</div>";
    fclose($fp);
} else {
    echo "<div style='color: red;'>✗ Network connection failed: $errstr ($errno)</div>";
}

// Test 5: Coba dari service variables yang lain
echo "<h4>Test 5: Alternative Hosts</h4>";
$alternatives = [
    ['host' => 'mysql.railway.internal', 'port' => 58371],
    ['host' => 'containers-us-west-145.railway.app', 'port' => 58371], // Mungkin host external
    ['host' => '127.0.0.1', 'port' => 58371],
    ['host' => 'localhost', 'port' => 58371],
];

foreach ($alternatives as $alt) {
    try {
        $dsn = "mysql:host={$alt['host']};port={$alt['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        echo "<div style='color: green;'>✓ {$alt['host']}:{$alt['port']} - Connected</div>";
    } catch (PDOException $e) {
        echo "<div style='color: orange;'>{$alt['host']}:{$alt['port']} - Failed: " . $e->getMessage() . "</div>";
    }
}

// Show all environment variables
echo "<h4>Environment Variables</h4>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'MYSQL') !== false || strpos($key, 'DATABASE') !== false || strpos($key, 'RAILWAY') !== false) {
        echo "$key = $value\n";
    }
}
echo "</pre>";
?>
