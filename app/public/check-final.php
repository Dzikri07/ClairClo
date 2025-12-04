<?php
// check-final.php
echo "<h2>Final Configuration Check</h2>";

echo "<h3>1. App Environment Variables (ClairoClo):</h3>";
echo "<pre>";
$app_vars = ['MYSQLHOST', 'MYSQLPORT', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD'];
foreach ($app_vars as $var) {
    $value = getenv($var);
    $display = $value;
    
    if ($var === 'MYSQLPASSWORD') {
        $display = substr($value, 0, 4) . "****";
    }
    
    // Highlight jika ada yang salah
    if ($var === 'MYSQLPORT' && $value == '58371') {
        echo "<span style='color: red;'>⚠️ $var: $display ← WRONG! Should be 3306</span>\n";
    } else if ($var === 'MYSQLPORT' && $value == '3306') {
        echo "<span style='color: green;'>✓ $var: $display ← CORRECT</span>\n";
    } else {
        echo "$var: $display\n";
    }
}
echo "</pre>";

echo "<h3>2. Connection Test:</h3>";

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

if ($host && $port && $db && $user && $pass) {
    echo "Testing: mysql://{$user}:***@{$host}:{$port}/{$db}<br>";
    
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$db}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "<p style='color: green;'>✓ CONNECTED using App ENV variables</p>";
        
        // Show info
        $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user");
        $info = $stmt->fetch();
        echo "Database: {$info['db']}<br>";
        echo "User: {$info['user']}<br>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ FAILED: " . $e->getMessage() . "</p>";
        
        // Test hardcoded
        echo "<h4>Trying hardcoded (3306):</h4>";
        try {
            $dsn = "mysql:host=mysql.railway.internal;port=3306;dbname=railway";
            $pdo = new PDO($dsn, 'root', 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl');
            echo "<p style='color: green;'>✓ Hardcoded connection works!</p>";
            echo "<p>Conclusion: Update MYSQLPORT to 3306 in App variables</p>";
        } catch (Exception $e2) {
            echo "<p style='color: red;'>✗ Hardcoded also failed: " . $e2->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>Some environment variables missing</p>";
}

echo "<h3>3. Action Required:</h3>";
echo "<ol>";
echo "<li>Go to Railway → ClairoClo app service → Variables</li>";
echo "<li>Change <strong>MYSQLPORT from 58371 to 3306</strong></li>";
echo "<li>Deploy/redeploy the app</li>";
echo "<li>Test login again</li>";
echo "</ol>";
?>