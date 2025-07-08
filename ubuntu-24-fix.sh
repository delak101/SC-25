#!/bin/bash
# Ubuntu 24.10 (Oracular) Compatible Deployment Script

echo "🔧 Fixing deployment for Ubuntu 24.10..."

# Check Ubuntu version
echo "📋 Checking Ubuntu version..."
lsb_release -a

# Stop Apache if running (it conflicts with Nginx)
echo "🛑 Stopping Apache..."
systemctl stop apache2 || true
systemctl disable apache2 || true

# Install correct PHP version for Ubuntu 24.10
echo "📦 Installing PHP 8.3 (correct for Ubuntu 24.10)..."
apt update
apt install -y nginx php8.3-fpm php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-zip

# Copy files properly
echo "📂 Copying application files..."
cd /var/www/html/silentconnect/SC-25
cp -r * /var/www/html/
cd /var/www/html

# Configure Nginx for PHP 8.3
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/default << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    server_name _;
    root /var/www/html;
    index index.php index.html;

    # Increase client max body size
    client_max_body_size 50M;

    # PHP handling (updated for PHP 8.3)
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
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
}
EOF

# Configure PHP 8.3
echo "⚙️ Configuring PHP 8.3..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/uploads /var/www/html/logs /var/www/html/database 2>/dev/null || true
chmod 600 /var/www/html/.env 2>/dev/null || true

# Create required directories
mkdir -p /var/www/html/{uploads,logs,database}
mkdir -p /var/www/html/uploads/{videos,national_ids,service_cards,profiles}
chown -R www-data:www-data /var/www/html/{uploads,logs,database}

# Start services
echo "🔄 Starting services..."
systemctl enable nginx
systemctl enable php8.3-fpm
systemctl restart php8.3-fpm
systemctl restart nginx

echo "✅ Deployment fixed!"
echo "🌐 Testing: http://104.236.102.224"
