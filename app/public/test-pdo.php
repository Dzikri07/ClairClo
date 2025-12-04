<?php
// test-pdo.php - Test multiple connections
echo "<h2>MySQL Connection Tests</h2>";

$testCases = [
    // Railway internal (default)
    [
        'name' => 'Railway Internal',
        'host' => 'mysql.railway.internal',
        'port' => 58371,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // Mungkin butuh external host
    [
        'name' => 'Railway External (Guess)',
        'host' => 'containers-us-west-145.railway.app',
        'port' => 58371,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // Localhost (jika MySQL di service yang sama)
    [
        'name' => 'Localhost',
        'host' => '127.0.0.1',
        'port' => 3306,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // Tanpa database name
    [
        'name' => 'Railway Internal (no db)',
        'host' => 'mysql.railway.internal',
        'port' => 58371,
        'db' => '',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ]
];

foreach ($testCases as $test) {
    echo "<h3>Test: {$test['name']}</h3>";
    echo "<pre>";
    echo "Host: {$test['host']}\n";
    echo "Port: {$test['port']}\n";
    echo "DB: {$test['db']}\n";
    echo "User: {$test['user']}\n";
    echo "</pre>";
    
    try {
        if ($test['db']) {
            $dsn = "mysql:host={$test['host']};port={$test['port']};dbname={$test['db']}";
        } else {
            $dsn = "mysql:host={$test['host']};port={$test['port']}";
        }
        
        $pdo = new PDO($dsn, $test['user'], $test['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "<p style='color: green;'>✓ CONNECTED</p>";
        
        // Try simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "<p>Query result: {$result['test']}</p>";
        
        // Show database info
        $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user");
        $info = $stmt->fetch();
        echo "<p>Database: {$info['db']}, User: {$info['user']}</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ FAILED: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Cek network connectivity
echo "<h3>Network Connectivity Test</h3>";
$host = 'mysql.railway.internal';
$port = 58371;

$start = microtime(true);
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
$end = microtime(true);

if ($socket) {
    echo "<p style='color: green;'>✓ Network reachable (" . round(($end - $start) * 1000, 2) . " ms)</p>";
    fclose($socket);
} else {
    echo "<p style='color: red;'>✗ Network unreachable: $errstr ($errno)</p>";
    echo "<p>Mungkin MySQL service tidak berjalan atau network blocked</p>";
}
?>