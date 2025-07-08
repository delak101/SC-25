<?php
/**
 * Debug Script for Silent Connect
 * Run this to check what's wrong
 */

echo "🔍 Silent Connect Debug Report\n";
echo "==============================\n\n";

// Check if we can load config
echo "1. Testing Configuration...\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "   ✅ Configuration loaded successfully\n";
    echo "   📊 APP_URL: " . APP_URL . "\n";
    echo "   📊 DB_TYPE: " . DB_TYPE . "\n";
    if (DB_TYPE === 'sqlite') {
        echo "   📊 DB_PATH: " . DB_PATH . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Configuration failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check database connection
echo "\n2. Testing Database...\n";
try {
    $db = Database::getInstance();
    echo "   ✅ Database connection successful\n";
    
    // Test query
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "   📊 Users table has " . $result['count'] . " records\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Check Auth class
echo "\n3. Testing Auth Class...\n";
try {
    require_once __DIR__ . '/classes/Auth.php';
    $auth = new Auth();
    echo "   ✅ Auth class loaded successfully\n";
} catch (Exception $e) {
    echo "   ❌ Auth class error: " . $e->getMessage() . "\n";
}

// Check file permissions
echo "\n4. Checking File Permissions...\n";
$dirs_to_check = ['uploads', 'logs', 'database', 'config'];
foreach ($dirs_to_check as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        $writable = is_writable(__DIR__ . '/' . $dir);
        echo "   " . ($writable ? "✅" : "❌") . " $dir/ " . ($writable ? "writable" : "not writable") . "\n";
    } else {
        echo "   ❌ $dir/ directory missing\n";
    }
}

// Check critical files
echo "\n5. Checking Critical Files...\n";
$files_to_check = [
    '.env',
    'config/config.php',
    'classes/Database.php',
    'classes/Auth.php',
    'handlers/login.php'
];

foreach ($files_to_check as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Check PHP version and extensions
echo "\n6. PHP Environment...\n";
echo "   📊 PHP Version: " . PHP_VERSION . "\n";

$required_extensions = ['pdo', 'pdo_sqlite', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   " . ($loaded ? "✅" : "❌") . " $ext extension " . ($loaded ? "loaded" : "missing") . "\n";
}

// Test login functionality
echo "\n7. Testing Login Handler...\n";
try {
    // Simulate a basic test (don't actually try to login)
    if (file_exists(__DIR__ . '/handlers/login.php')) {
        echo "   ✅ Login handler file exists\n";
        
        // Check if we can at least parse the file
        $content = file_get_contents(__DIR__ . '/handlers/login.php');
        if (strpos($content, '$auth') !== false) {
            echo "   ✅ Login handler references auth object\n";
        } else {
            echo "   ❌ Login handler missing auth object reference\n";
        }
    } else {
        echo "   ❌ Login handler file missing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Login handler error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 Debug completed!\n";

// Display recent error logs if available
if (file_exists(__DIR__ . '/logs/error.log')) {
    echo "\n📋 Recent Error Log (last 10 lines):\n";
    echo str_repeat("-", 50) . "\n";
    $lines = file(__DIR__ . '/logs/error.log');
    $recent_lines = array_slice($lines, -10);
    foreach ($recent_lines as $line) {
        echo trim($line) . "\n";
    }
}

?>
