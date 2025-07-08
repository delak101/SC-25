<?php
/**
 * Migration Script: Add Verification Tokens Table
 * Run this script to add the missing verification_tokens table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Running migration: Add verification_tokens table...\n";
    
    // Read the migration SQL file
    $sql = file_get_contents(__DIR__ . '/add_verification_tokens_table.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read migration file');
    }
    
    // Split into individual statements and execute each one
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "Migration completed successfully!\n";
    echo "The verification_tokens table has been created.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
