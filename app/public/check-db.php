<?php
// check-db.php
$host = 'mysql.railway.internal';
$port = 58371;
$user = 'root';
$pass = 'lEgTlAziFBDuKzVkbWRYjJihcTzkchVl';

try {
    // Connect tanpa database dulu
    $dsn = "mysql:host=$host;port=$port";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h3>Connected to MySQL Server</h3>";
    
    // List databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h4>Available Databases:</h4>";
    echo "<ul>";
    foreach ($databases as $db) {
        $checked = ($db == 'railway') ? ' ✅' : '';
        echo "<li>$db$checked</li>";
    }
    echo "</ul>";
    
    // Check if 'railway' exists
    if (in_array('railway', $databases)) {
        echo "<p style='color: green;'>✓ Database 'railway' EXISTS</p>";
        
        // Try connect to railway database
        $dsn = "mysql:host=$host;port=$port;dbname=railway";
        $pdo2 = new PDO($dsn, $user, $pass);
        
        // List tables
        $stmt = $pdo2->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h4>Tables in 'railway' database:</h4>";
        if ($tables) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No tables found (empty database)</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database 'railway' NOT FOUND</p>";
        echo "<p>You need to create the database or import your schema</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Connection failed: " . $e->getMessage() . "</p>";
}
?>