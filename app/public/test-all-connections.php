<?php
// test-all-connections.php
echo "<h2>Test All MySQL Connections</h2>";

$tests = [
    // Internal dengan port 58371 (CORRECT)
    [
        'name' => 'Internal Correct (58371)',
        'host' => 'mysql.railway.internal',
        'port' => 58371,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // Internal dengan port 3306 (WRONG - tapi coba)
    [
        'name' => 'Internal Wrong (3306)',
        'host' => 'mysql.railway.internal',
        'port' => 3306,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // External dengan port 58371 (CORRECT)
    [
        'name' => 'External Correct (58371)',
        'host' => 'ballast.proxy.rlwy.net',
        'port' => 58371,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ],
    // External dengan port 50371 (WRONG - dari MYSQL_PUBLIC_URL)
    [
        'name' => 'External Wrong (50371)',
        'host' => 'ballast.proxy.rlwy.net',
        'port' => 50371,
        'db' => 'railway',
        'user' => 'root',
        'pass' => 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl'
    ]
];

foreach ($tests as $test) {
    echo "<h3>{$test['name']}</h3>";
    echo "<pre>";
    echo "Host: {$test['host']}\n";
    echo "Port: {$test['port']}\n";
    echo "DB: {$test['db']}\n";
    echo "User: {$test['user']}\n";
    echo "</pre>";
    
    try {
        $dsn = "mysql:host={$test['host']};port={$test['port']};dbname={$test['db']}";
        $pdo = new PDO($dsn, $test['user'], $test['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "<p style='color: green;'>✓ CONNECTED</p>";
        
        // Show info
        $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user, VERSION() as version");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($info);
        echo "</pre>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ FAILED: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Summary
echo "<h2>Conclusion:</h2>";
echo "<p>Based on your configuration:</p>";
echo "<ul>";
echo "<li>✅ Use PORT: <strong>58371</strong> (from MYSQLPORT variable)</li>";
echo "<li>❌ PORT 3306 is wrong for Railway</li>";
echo "<li>❌ PORT 50371 is wrong (typo in MYSQL_PUBLIC_URL)</li>";
echo "</ul>";
echo "<p><strong>Action:</strong> Update MYSQL_URL and MYSQL_PUBLIC_URL to use port 58371</p>";
?>