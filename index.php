<?php
/**
 * Main Application Entry Point
 * Handles initial routing and authentication check
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize database if needed
try {
    $db = Database::getInstance();
    
    // Check if tables exist, if not initialize them
    if (!$db->tableExists('users')) {
        $db->initializeTables();
        
        // Initialize RBAC defaults if this is first run
        if (!$rbac->getRoleByName('admin')) {
            $rbac->initializeDefaults();
        }
    }
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

// Check if user is authenticated
if (isAuthenticated()) {
    // User is logged in, redirect to dashboard
    header('Location: /dashboard.php');
    exit;
} else {
    // User is not logged in, show login page
    header('Location: /login.php');
    exit;
}
?>
