<?php
/**
 * Migration: Add view_count column to videos table
 */

require_once __DIR__ . '/../classes/Database.php';

try {
    echo "Starting migration: Add view_count column to videos table...\n";
    
    $db = Database::getInstance();
    
    // Check if column already exists
    $stmt = $db->prepare("SHOW COLUMNS FROM videos LIKE 'view_count'");
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "Column 'view_count' already exists in videos table.\n";
    } else {
        // Add view_count column
        $db->exec("ALTER TABLE videos ADD COLUMN view_count INT DEFAULT 0 NOT NULL");
        echo "Added 'view_count' column to videos table.\n";
        
        // Update existing videos to have 0 view count
        $db->exec("UPDATE videos SET view_count = 0 WHERE view_count IS NULL");
        echo "Updated existing videos with default view count.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
