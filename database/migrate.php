<?php
/**
 * migrate.php - Database migration runner
 * Usage: php migrate.php [up|down|reset|status]
 */

// Load connection
require_once __DIR__ . '/../app/public/connection.php';

class MigrationRunner
{
    private $pdo;
    private $migrationsPath;
    private $migrationsTable = 'migrations';

    public function __construct()
    {
        try {
            $this->pdo = getDB();
            $this->migrationsPath = __DIR__ . '/migrations';
            $this->createMigrationsTable();
        } catch (Exception $e) {
            die("Error: Could not connect to database. " . $e->getMessage() . "\n");
        }
    }

    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT UNSIGNED NOT NULL,
            `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->pdo->exec($sql);
    }

    /**
     * Get all migration files
     */
    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        return $files;
    }

    /**
     * Get executed migrations
     */
    private function getExecutedMigrations()
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations()
    {
        $allFiles = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        $pending = [];
        foreach ($allFiles as $file) {
            $migrationName = basename($file);
            if (!in_array($migrationName, $executed)) {
                $pending[] = $file;
            }
        }
        
        return $pending;
    }

    /**
     * Get next batch number
     */
    private function getNextBatch()
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        $result = $stmt->fetch();
        return ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Run pending migrations
     */
    public function up()
    {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            echo "No pending migrations.\n";
            return;
        }

        $batch = $this->getNextBatch();
        echo "Running " . count($pending) . " migration(s)...\n";
        echo str_repeat("=", 60) . "\n";

        foreach ($pending as $file) {
            $migrationName = basename($file);
            echo "\nMigration: {$migrationName}\n";
            echo str_repeat("-", 60) . "\n";

            try {
                require_once $file;
                
                // Get class name from file
                $className = $this->getClassNameFromFile($file);
                
                if (!class_exists($className)) {
                    echo "Error: Class {$className} not found in {$migrationName}\n";
                    continue;
                }

                $migration = new $className($this->pdo);
                
                // Run migration
                if ($migration->up()) {
                    // Record migration
                    $stmt = $this->pdo->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
                    $stmt->execute([$migrationName, $batch]);
                    echo "✓ Migration completed successfully\n";
                } else {
                    echo "✗ Migration failed\n";
                }
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Migration completed!\n";
    }

    /**
     * Rollback last batch of migrations
     */
    public function down()
    {
        // Get last batch
        $stmt = $this->pdo->query("SELECT MAX(batch) as last_batch FROM {$this->migrationsTable}");
        $result = $stmt->fetch();
        $lastBatch = $result['last_batch'] ?? 0;

        if ($lastBatch == 0) {
            echo "No migrations to rollback.\n";
            return;
        }

        // Get migrations from last batch
        $stmt = $this->pdo->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "Rolling back batch {$lastBatch} (" . count($migrations) . " migration(s))...\n";
        echo str_repeat("=", 60) . "\n";

        foreach ($migrations as $migrationName) {
            echo "\nRollback: {$migrationName}\n";
            echo str_repeat("-", 60) . "\n";

            try {
                $file = $this->migrationsPath . '/' . $migrationName;
                
                if (!file_exists($file)) {
                    echo "Error: Migration file not found\n";
                    continue;
                }

                require_once $file;
                
                $className = $this->getClassNameFromFile($file);
                
                if (!class_exists($className)) {
                    echo "Error: Class {$className} not found\n";
                    continue;
                }

                $migration = new $className($this->pdo);
                
                // Rollback migration
                if ($migration->down()) {
                    // Remove migration record
                    $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
                    $stmt->execute([$migrationName]);
                    echo "✓ Rollback completed successfully\n";
                } else {
                    echo "✗ Rollback failed\n";
                }
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Rollback completed!\n";
    }

    /**
     * Reset all migrations (rollback all and re-run)
     */
    public function reset()
    {
        echo "Resetting all migrations...\n\n";
        
        // Rollback all
        while (true) {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->migrationsTable}");
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $this->down();
            echo "\n";
        }
        
        echo "All migrations rolled back. Running migrations...\n\n";
        $this->up();
    }

    /**
     * Show migration status
     */
    public function status()
    {
        $allFiles = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        echo "Migration Status\n";
        echo str_repeat("=", 80) . "\n";
        printf("%-50s %s\n", "Migration", "Status");
        echo str_repeat("-", 80) . "\n";

        foreach ($allFiles as $file) {
            $migrationName = basename($file);
            $status = in_array($migrationName, $executed) ? "✓ Executed" : "✗ Pending";
            printf("%-50s %s\n", $migrationName, $status);
        }

        echo str_repeat("=", 80) . "\n";
        echo "Total: " . count($allFiles) . " migrations\n";
        echo "Executed: " . count($executed) . " migrations\n";
        echo "Pending: " . (count($allFiles) - count($executed)) . " migrations\n";
    }

    /**
     * Get class name from migration file
     */
    private function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        
        // Extract class name using regex
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/i', $content, $matches)) {
            return $matches[1];
        }
        
        // Fallback: convert filename to class name
        $filename = basename($file, '.php');
        $parts = explode('_', $filename);
        array_shift($parts); // Remove number prefix
        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'status';
    
    $runner = new MigrationRunner();
    
    switch ($command) {
        case 'up':
            $runner->up();
            break;
        case 'down':
            $runner->down();
            break;
        case 'reset':
            $runner->reset();
            break;
        case 'status':
            $runner->status();
            break;
        default:
            echo "Usage: php migrate.php [up|down|reset|status]\n";
            echo "\n";
            echo "Commands:\n";
            echo "  up      - Run pending migrations\n";
            echo "  down    - Rollback last batch of migrations\n";
            echo "  reset   - Rollback all migrations and re-run\n";
            echo "  status  - Show migration status\n";
            break;
    }
}
