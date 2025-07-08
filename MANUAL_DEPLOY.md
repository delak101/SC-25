# Manual Deployment Steps (You're Already on the Server!)

Since you're already on your DigitalOcean server, let's complete the deployment manually:

## 1. Fix Package Issues (run these commands on your server):

```bash
# Fix broken packages
apt --fix-broken install -y
apt autoremove -y

# Install required packages without upgrade
apt install -y nginx php8.1-fpm php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-gd php8.1-zip
```

## 2. Configure Nginx:

```bash
# Create Nginx configuration
cat > /etc/nginx/sites-available/silentconnect << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    server_name 104.236.102.224 localhost _;
    root /var/www/html;
    index index.php index.html;

    client_max_body_size 50M;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location /api/ {
        try_files $uri $uri/ /api/v1/index.php?$query_string;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\. { deny all; }
    location ~ /(config|classes|migrations)/ { deny all; }
    location ~ /\.env { deny all; }
}
EOF

# Enable site
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/silentconnect /etc/nginx/sites-enabled/
```

## 3. Configure PHP:

```bash
# Update PHP settings
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.1/fmp/php.ini
```

## 4. Set Permissions:

```bash
# Navigate to your app directory
cd /var/www/html/silentconnect/SC-25

# Copy files to web root
cp -r * /var/www/html/

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/uploads /var/www/html/logs /var/www/html/database
chmod 600 /var/www/html/.env

# Create required directories
mkdir -p /var/www/html/{uploads,logs,database}
mkdir -p /var/www/html/uploads/{videos,national_ids,service_cards,profiles}
chown -R www-data:www-data /var/www/html/{uploads,logs,database}
```

## 5. Restart Services:

```bash
# Test and restart
nginx -t
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl enable nginx
systemctl enable php8.1-fpm
```

## 6. Initialize Database:

```bash
# Test configuration
php /var/www/html/config/config.php

# Visit setup page
curl http://104.236.102.224/setup.php
# OR visit in browser: http://104.236.102.224/setup.php
```

## 7. Access Your Application:

- **Main Site:** http://104.236.102.224
- **Setup:** http://104.236.102.224/setup.php  
- **Login:** http://104.236.102.224/login.php
- **API:** http://104.236.102.224/api/v1/

**Default Login:**
- Email: admin@silentconnect.com
- Password: Admin123
