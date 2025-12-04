<?php
// verify-ports.php
echo "<h2>Final Port Verification</h2>";

echo "<h3>Current Configuration:</h3>";
echo "<pre>";
echo "MYSQLPORT env: " . (getenv('MYSQLPORT') ?: 'NOT SET') . "\n";
echo "MYSQL_URL: " . (getenv('MYSQL_URL') ?: 'NOT SET') . "\n";
echo "MYSQL_PUBLIC_URL: " . (getenv('MYSQL_PUBLIC_URL') ?: 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>Working Ports (from previous test):</h3>";
echo "<ul>";
echo "<li><strong>mysql.railway.internal:3306</strong> ✓ WORKING (Internal)</li>";
echo "<li><strong>ballast.proxy.rlwy.net:50371</strong> ✓ WORKING (External)</li>";
echo "<li><strong>mysql.railway.internal:58371</strong> ✗ NOT WORKING</li>";
echo "<li><strong>ballast.proxy.rlwy.net:58371</strong> ✗ NOT WORKING</li>";
echo "</ul>";

echo "<h3>Recommendation:</h3>";
echo "<p>Update these variables in Railway:</p>";
echo "<pre>";
echo "MYSQLPORT = 3306\n";
echo "MYSQL_URL = mysql://root:lEgTlAziFBDuKzVkbWRYjJihcTzkchVl@mysql.railway.internal:3306/railway\n";
echo "MYSQL_PUBLIC_URL = mysql://root:lEgTlAziFBDuKzVkbWRYjJihcTzkchVl@ballast.proxy.rlwy.net:50371/railway\n";
echo "</pre>";

// Quick test
echo "<h3>Quick Connection Test:</h3>";

// Test 1: Internal
try {
    $dsn = "mysql:host=mysql.railway.internal;port=3306;dbname=railway";
    $pdo = new PDO($dsn, 'root', 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✓ Internal (3306): Connected</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Internal (3306): " . $e->getMessage() . "</p>";
}

// Test 2: External
try {
    $dsn = "mysql:host=ballast.proxy.rlwy.net;port=50371;dbname=railway";
    $pdo = new PDO($dsn, 'root', 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "<p style='color: green;'>✓ External (50371): Connected</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ External (50371): " . $e->getMessage() . "</p>";
}
?>