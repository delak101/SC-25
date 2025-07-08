# Silent Connect Deployment Guide

## Environment Setup

### 1. Create .env file
Copy `.env.example` to `.env` and update the values:

```bash
# For local development
cp .env.example .env

# For production deployment
scp .env.example root@104.236.102.224:/var/www/html/.env
```

### 2. Configure .env for your deployment

Edit the `.env` file with your actual values:

```ini
# Application Settings
APP_ENV=production
APP_NAME="Silent Connect"
APP_URL=http://104.236.102.224

# Database Settings (SQLite - simpler for initial deployment)
DB_TYPE=sqlite
DB_PATH=database/silent_connect.db

# OR MySQL if you prefer
# DB_TYPE=mysql
# DB_HOST=localhost
# DB_NAME=silent_connect
# DB_USER=silent_connect_user
# DB_PASS=YourSecurePassword123!
# DB_PORT=3306

# Security Settings (CHANGE THESE!)
ENCRYPTION_KEY=YourUniqueEncryptionKey2024
SESSION_LIFETIME=28800
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=300

# Admin User
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=YourSecureAdminPassword123!

# File Upload Settings
UPLOAD_PATH=uploads/
```

### 3. Deployment Commands

```bash
# Connect to your server
ssh root@104.236.102.224

# Upload your files
scp -r SilentConnect\ 2.4.4/* root@104.236.102.224:/var/www/html/

# Set up environment
cd /var/www/html
cp .env.example .env
nano .env  # Edit with your values

# Set permissions
chmod 600 .env  # Secure the environment file
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 uploads database logs

# Initialize database
php -r "require 'config/config.php'; echo 'Config loaded successfully';"
```

### 4. Access your application

Once deployed, access:
- Main site: http://104.236.102.224
- API Info: http://104.236.102.224/api/v1/
- Login: http://104.236.102.224/login.php

### 5. Security Notes

- Never commit `.env` to version control
- Change default passwords immediately
- Use strong encryption keys
- Consider adding SSL certificate later
- Restrict database access in production

### 6. Troubleshooting

If you get environment errors:
1. Check `.env` file exists and has correct permissions
2. Verify file paths in configuration
3. Check PHP error logs: `tail -f /var/log/nginx/error.log`
4. Test configuration: `php -r "require 'config/config.php'; var_dump($_ENV);"`
