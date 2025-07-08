#!/bin/bash
# Simple deployment script for Silent Connect on DigitalOcean
# Usage: ./deploy.sh

set -e  # Exit on any error

echo "🚀 Deploying Silent Connect to DigitalOcean..."

# Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install required packages
echo "📦 Installing required packages..."
apt install -y nginx php8.1-fpm php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-gd php8.1-zip php8.1-mysql unzip

# Create web directory if it doesn't exist
mkdir -p /var/www/html

# Set proper ownership
chown -R www-data:www-data /var/www/html

# Configure Nginx
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/silentconnect << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    server_name 104.236.102.224 localhost _;
    root /var/www/html;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Increase client max body size for file uploads
    client_max_body_size 50M;

    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # API routes
    location /api/ {
        try_files $uri $uri/ /api/v1/index.php?$query_string;
    }

    # General URL rewriting
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Security - deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(config|classes|migrations)/ {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }

    # Static file handling with caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|mp4|avi|mov)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
EOF

# Enable site
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/silentconnect /etc/nginx/sites-enabled/

# Configure PHP-FPM
echo "⚙️  Configuring PHP..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.1/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_input_vars = .*/max_input_vars = 3000/' /etc/php/8.1/fpm/php.ini

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/logs 2>/dev/null || true
chmod -R 775 /var/www/html/database 2>/dev/null || true
chmod 600 /var/www/html/.env 2>/dev/null || true

# Create required directories
mkdir -p /var/www/html/{uploads,logs,database}
mkdir -p /var/www/html/uploads/{videos,national_ids,service_cards,profiles}
chown -R www-data:www-data /var/www/html/{uploads,logs,database}

# Test and restart services
echo "🔄 Restarting services..."
nginx -t && systemctl restart nginx
systemctl restart php8.1-fpm

# Enable services to start on boot
systemctl enable nginx
systemctl enable php8.1-fpm

echo "✅ Deployment completed!"
echo "🌐 Your application should be accessible at: http://104.236.102.224"
echo "🔧 Run the setup script next: php /var/www/html/setup.php"
