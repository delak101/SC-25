<?php
/**
 * Add clinic_id column to videos table migration
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Get database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Starting migration: Add clinic_id column to videos table...\n";
    
    // Check if column already exists based on database type
    $clinicIdExists = false;
    
    if (DB_TYPE === 'sqlite') {
        // SQLite check
        $checkQuery = "PRAGMA table_info(videos)";
        $stmt = $pdo->query($checkQuery);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if ($column['name'] === 'clinic_id') {
                $clinicIdExists = true;
                break;
            }
        }
    } else {
        // MySQL check
        $checkQuery = "SHOW COLUMNS FROM videos LIKE 'clinic_id'";
        $stmt = $pdo->query($checkQuery);
        $clinicIdExists = $stmt->rowCount() > 0;
    }
    
    if ($clinicIdExists) {
        echo "Column 'clinic_id' already exists in videos table.\n";
    } else {
        // Add clinic_id column
        if (DB_TYPE === 'sqlite') {
            $pdo->exec("ALTER TABLE videos ADD COLUMN clinic_id INTEGER");
        } else {
            $pdo->exec("ALTER TABLE videos ADD COLUMN clinic_id INT");
        }
        echo "Added 'clinic_id' column to videos table.\n";
        
        // Add foreign key constraint (MySQL only, SQLite handles this differently)
        if (DB_TYPE !== 'sqlite') {
            try {
                $pdo->exec("ALTER TABLE videos ADD CONSTRAINT fk_videos_clinic_id FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL");
                echo "Added foreign key constraint.\n";
            } catch (Exception $e) {
                echo "Note: Could not add foreign key constraint (may already exist): " . $e->getMessage() . "\n";
            }
        }
        
        // Create index
        if (DB_TYPE === 'sqlite') {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_videos_clinic_id ON videos(clinic_id)");
        } else {
            try {
                $pdo->exec("CREATE INDEX idx_videos_clinic_id ON videos(clinic_id)");
            } catch (Exception $e) {
                echo "Note: Could not create index (may already exist): " . $e->getMessage() . "\n";
            }
        }
        echo "Created index on 'clinic_id' column.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
