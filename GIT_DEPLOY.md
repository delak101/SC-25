# 🚀 Git-Based Deployment Guide

## Step 1: Push to Git (run locally)
```bash
git add .
git commit -m "Ready for production deployment with Ubuntu 24.10 fixes"
git push origin main
```

## Step 2: Deploy on Server (run on your DigitalOcean server)

### Pull latest code:
```bash
cd /var/www/html
git pull origin main
```

### Run the fixed deployment script:
```bash
chmod +x ubuntu-24-fix.sh
./ubuntu-24-fix.sh
```

### Initialize database:
```bash
# Method 1: Run setup via web
# Visit: http://104.236.102.224/setup.php

# Method 2: Command line setup
php setup.php

# Method 3: Quick database init
php -r "
require 'config/config.php';
\$db = Database::getInstance();
if (!\$db->tableExists('users')) {
    echo 'Initializing database...\n';
    \$db->initializeTables();
    \$hashedPassword = password_hash('Admin123', PASSWORD_DEFAULT);
    \$stmt = \$db->prepare('INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    \$stmt->execute(['مدير النظام', 'admin@silentconnect.com', \$hashedPassword, 'admin', 'active']);
    echo 'Database initialized successfully!\n';
} else {
    echo 'Database already initialized.\n';
}
"
```

### Test the deployment:
```bash
# Run debug script
php debug.php

# Test web access
curl -I http://104.236.102.224
curl -I http://104.236.102.224/setup.php
```

### Fix any remaining issues:
```bash
# Check logs if needed
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.3-fpm.log

# Fix permissions if needed
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 uploads logs database
chmod 600 .env
```

## Step 3: Access Your Application

After successful deployment:

- **Main Site:** http://104.236.102.224
- **Setup Page:** http://104.236.102.224/setup.php  
- **Login Page:** http://104.236.102.224/login.php
- **API Docs:** http://104.236.102.224/api/v1/
- **Debug Info:** http://104.236.102.224/debug.php

**Default Credentials:**
- Email: admin@silentconnect.com
- Password: Admin123

## Troubleshooting Commands

If something goes wrong:

```bash
# Check service status
systemctl status nginx
systemctl status php8.3-fpm

# Restart services
systemctl restart nginx php8.3-fpm

# Check error logs
journalctl -u nginx -f
journalctl -u php8.3-fpm -f

# Test PHP
php -v
php -m | grep sqlite

# Check file permissions
ls -la /var/www/html/
ls -la /var/www/html/.env
```

## Summary

1. **Git push** (local)
2. **Git pull** (server) 
3. **Run ubuntu-24-fix.sh** (server)
4. **Initialize database** (server)
5. **Test and access** your app!

Total time: ~5-10 minutes
