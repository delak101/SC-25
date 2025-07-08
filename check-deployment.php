<?php
/**
 * Pre-deployment verification script
 * Run this locally to check if everything is ready for deployment
 */

echo "🔍 Silent Connect Pre-Deployment Check\n";
echo "=====================================\n\n";

$errors = [];
$warnings = [];
$checks = 0;

// Check required files
$required_files = [
    '.env',
    'config/config.php',
    'config/database.php',
    'classes/Database.php',
    'migrations/init_sqlite.sql',
    'deploy.sh',
    'setup.php',
    'index.php',
    'login.php'
];

echo "📁 Checking required files...\n";
foreach ($required_files as $file) {
    $checks++;
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file\n";
    } else {
        $errors[] = "Missing file: $file";
        echo "❌ $file (MISSING)\n";
    }
}

// Check .env configuration
echo "\n⚙️  Checking .env configuration...\n";
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $required_env = ['APP_URL', 'DB_TYPE', 'ADMIN_EMAIL', 'ADMIN_PASSWORD'];
    
    foreach ($required_env as $key) {
        $checks++;
        if (isset($env[$key]) && !empty($env[$key])) {
            echo "✅ $key = " . $env[$key] . "\n";
        } else {
            $errors[] = "Missing or empty environment variable: $key";
            echo "❌ $key (MISSING)\n";
        }
    }
    
    // Check if IP is set correctly
    if (isset($env['APP_URL']) && strpos($env['APP_URL'], '104.236.102.224') !== false) {
        echo "✅ IP address configured correctly\n";
    } else {
        $warnings[] = "APP_URL doesn't contain expected IP address";
    }
} else {
    $errors[] = "No .env file found";
}

// Check directory structure
echo "\n📂 Checking directory structure...\n";
$required_dirs = [
    'config',
    'classes',
    'migrations',
    'api/v1',
    'uploads',
    'database'
];

foreach ($required_dirs as $dir) {
    $checks++;
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "✅ $dir/\n";
    } else {
        $warnings[] = "Directory will be created during deployment: $dir";
        echo "⚠️  $dir/ (will be created)\n";
    }
}

// Check deploy script
echo "\n🚀 Checking deployment script...\n";
if (file_exists(__DIR__ . '/deploy.sh')) {
    if (is_readable(__DIR__ . '/deploy.sh')) {
        echo "✅ deploy.sh is readable\n";
    } else {
        $warnings[] = "deploy.sh may need execute permissions";
    }
} else {
    $errors[] = "deploy.sh missing";
}

// Summary
echo "\n📊 SUMMARY\n";
echo "==========\n";
echo "Total checks: $checks\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n\n";

if (!empty($errors)) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
    echo "\n❌ Please fix these errors before deployment.\n\n";
} else {
    echo "✅ NO CRITICAL ERRORS FOUND!\n\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "🎉 READY FOR DEPLOYMENT!\n\n";
    echo "📋 Deployment commands:\n";
    echo "1. Upload files:\n";
    echo "   scp -r \"SilentConnect 2.4.4\"/* root@104.236.102.224:/var/www/html/\n\n";
    echo "2. Deploy on server:\n";
    echo "   ssh root@104.236.102.224\n";
    echo "   chmod +x /var/www/html/deploy.sh\n";
    echo "   /var/www/html/deploy.sh\n\n";
    echo "3. Setup database:\n";
    echo "   Visit: http://104.236.102.224/setup.php\n\n";
    echo "4. Access application:\n";
    echo "   http://104.236.102.224\n";
} else {
    echo "❌ Fix errors before deployment.\n";
    exit(1);
}
?>
