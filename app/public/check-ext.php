<?php
// check-ext.php di folder public
echo "<h2>PHP Extension Check</h2>";

// Cek basic extensions
$exts = ['pdo', 'pdo_mysql', 'mysqli'];
foreach ($exts as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✓ $ext LOADED</p>";
    } else {
        echo "<p style='color: red;'>✗ $ext NOT LOADED</p>";
    }
}

// Cek PDO drivers
if (extension_loaded('pdo')) {
    echo "<h3>PDO Drivers:</h3>";
    echo "<pre>";
    print_r(PDO::getAvailableDrivers());
    echo "</pre>";
}

// Cek PHP info
echo "<h3>PHP Info:</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PHP Ini: " . php_ini_loaded_file() . "<br>";

// Simple connection test jika pdo_mysql ada
if (extension_loaded('pdo_mysql')) {
    echo "<h3>Connection Test:</h3>";
    
    $host = 'mysql.railway.internal';
    $port = 58371;
    $db = 'railway';
    $user = 'root';
    $pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<p style='color: green;'>✓ Connection SUCCESS!</p>";
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "Query result: " . $result['test'];
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Connection FAILED: " . $e->getMessage() . "</p>";
    }
}
?>
