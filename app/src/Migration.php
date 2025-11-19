<?php
/**
 * Migration.php - Base migration class
 * Provides helper methods for database migrations
 */

abstract class Migration
{
    protected $pdo;
    protected $migrationName;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->migrationName = get_class($this);
    }

    /**
     * Run the migration
     */
    abstract public function up();

    /**
     * Rollback the migration
     */
    abstract public function down();

    /**
     * Execute a SQL statement
     */
    protected function execute($sql)
    {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            echo "Error executing SQL: " . $e->getMessage() . "\n";
            echo "SQL: " . $sql . "\n";
            return false;
        }
    }

    /**
     * Check if a table exists
     */
    protected function tableExists($tableName)
    {
        // Some MySQL/MariaDB versions don't accept parameter markers for SHOW statements.
        // Safely quote the value and execute as a simple query instead of a prepared statement.
        $quoted = $this->pdo->quote($tableName);
        $stmt = $this->pdo->query("SHOW TABLES LIKE " . $quoted);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if a column exists in a table
     */
    protected function columnExists($tableName, $columnName)
    {
        // Use quoted literal for LIKE pattern to avoid sending parameter markers to the server
        $quoted = $this->pdo->quote($columnName);
        $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE " . $quoted;
        $stmt = $this->pdo->query($sql);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Drop table if exists
     */
    protected function dropTable($tableName)
    {
        return $this->execute("DROP TABLE IF EXISTS `{$tableName}`");
    }

    /**
     * Add column to table
     */
    protected function addColumn($tableName, $columnName, $definition)
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return $this->execute("ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
        }
        return true;
    }

    /**
     * Drop column from table
     */
    protected function dropColumn($tableName, $columnName)
    {
        if ($this->columnExists($tableName, $columnName)) {
            return $this->execute("ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`");
        }
        return true;
    }

    /**
     * Create index
     */
    protected function createIndex($tableName, $indexName, $columns)
    {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this->execute("CREATE INDEX `{$indexName}` ON `{$tableName}` ({$columnList})");
    }

    /**
     * Drop index
     */
    protected function dropIndex($tableName, $indexName)
    {
        return $this->execute("DROP INDEX `{$indexName}` ON `{$tableName}`");
    }

    /**
     * Log migration message
     */
    protected function log($message)
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$this->migrationName}: {$message}\n";
    }
}
