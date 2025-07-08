<?php
/**
 * Add specialization column to clinics table migration
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Get database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Starting migration: Add specialization column to clinics table...\n";
    
    // Check if column already exists based on database type
    $specializationExists = false;
    
    if (DB_TYPE === 'sqlite') {
        // SQLite check
        $checkQuery = "PRAGMA table_info(clinics)";
        $stmt = $pdo->query($checkQuery);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if ($column['name'] === 'specialization') {
                $specializationExists = true;
                break;
            }
        }
    } else {
        // MySQL check
        $checkQuery = "SHOW COLUMNS FROM clinics LIKE 'specialization'";
        $stmt = $pdo->query($checkQuery);
        $specializationExists = $stmt->rowCount() > 0;
    }
    
    if ($specializationExists) {
        echo "Column 'specialization' already exists in clinics table.\n";
    } else {
        // Add specialization column
        $pdo->exec("ALTER TABLE clinics ADD COLUMN specialization VARCHAR(255)");
        echo "Added 'specialization' column to clinics table.\n";
        
        // Create index
        if (DB_TYPE === 'sqlite') {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clinics_specialization ON clinics(specialization)");
        } else {
            $pdo->exec("CREATE INDEX idx_clinics_specialization ON clinics(specialization)");
        }
        echo "Created index on 'specialization' column.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
