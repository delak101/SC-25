#!/bin/bash
# One-command deployment after git pull
# Usage: ./quick_deploy_after_git.sh

echo "🚀 Quick deployment after git pull..."

# Run the Ubuntu 24.10 fix script
chmod +x ubuntu-24-fix.sh
./ubuntu-24-fix.sh

echo "🗄️ Initializing database..."
# Initialize database
php -r "
require 'config/config.php';
try {
    \$db = Database::getInstance();
    if (!\$db->tableExists('users')) {
        echo 'Setting up database...\n';
        \$db->initializeTables();
        \$hashedPassword = password_hash('Admin123', PASSWORD_DEFAULT);
        \$stmt = \$db->prepare('INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        \$stmt->execute(['مدير النظام', 'admin@silentconnect.com', \$hashedPassword, 'admin', 'active']);
        echo '✅ Database initialized!\n';
    } else {
        echo '✅ Database already exists\n';
    }
} catch (Exception \$e) {
    echo '❌ Database error: ' . \$e->getMessage() . '\n';
}
"

echo "🔍 Running final checks..."
# Run debug to verify everything works
php debug.php

echo ""
echo "🎉 Deployment complete!"
echo "🌐 Visit: http://104.236.102.224"
echo "🔐 Login: admin@silentconnect.com / Admin123"
