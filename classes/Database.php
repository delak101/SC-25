<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Database Class
 * Handles database connections and operations for both MySQL and SQLite
 */
class Database {
    private static $instance = null;
    private $connection;
    private $statement;

    private function __construct() {
        try {
            $this->connection = new PDO(DB_DSN, DB_USER, DB_PASS, DB_OPTIONS);
            
            // Enable foreign key constraints for SQLite
            if (DB_TYPE === 'sqlite') {
                $this->connection->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prepare a statement
     */
    public function prepare($sql) {
        $this->statement = $this->connection->prepare($sql);
        return $this->statement;
    }

    /**
     * Execute prepared statement
     */
    public function execute($params = []) {
        try {
            return $this->statement->execute($params);
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new Exception("Query execution failed");
        }
    }

    /**
     * Fetch single row
     */
    public function fetch() {
        return $this->statement->fetch();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll() {
        return $this->statement->fetchAll();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        try {
            if (DB_TYPE === 'mysql') {
                $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?";
                $this->prepare($sql);
                $this->execute([DB_NAME, $tableName]);
            } else {
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = ?";
                $this->prepare($sql);
                $this->execute([$tableName]);
            }
            return $this->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute raw SQL (for migrations)
     */
    public function exec($sql) {
        return $this->connection->exec($sql);
    }

    /**
     * Get database type specific SQL
     */
    public function getDbSpecificSql($mysql_sql, $sqlite_sql) {
        return DB_TYPE === 'mysql' ? $mysql_sql : $sqlite_sql;
    }

    /**
     * Initialize database tables if they don't exist
     */
    public function initializeTables() {
        try {
            $migration_file = DB_TYPE === 'mysql' ? 
                __DIR__ . '/../migrations/init_mysql.sql' : 
                __DIR__ . '/../migrations/init_sqlite.sql';

            if (file_exists($migration_file)) {
                $sql = file_get_contents($migration_file);
                
                // Remove comments and normalize line endings
                $sql = preg_replace('/--.*$/m', '', $sql);
                $sql = str_replace(["\r\n", "\r"], "\n", $sql);
                
                // Split by semicolon more carefully
                $statements = preg_split('/;\s*(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && strlen($statement) > 3) {
                        try {
                            $this->exec($statement);
                        } catch (Exception $e) {
                            // Log individual statement errors but continue
                            error_log("SQL statement failed: " . substr($statement, 0, 100) . "... Error: " . $e->getMessage());
                        }
                    }
                }
                
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Database initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}
?>
