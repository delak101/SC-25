#!/bin/bash
# One-command deployment for Silent Connect
# Usage: curl -s https://your-server/quick-deploy.sh | bash

set -e

echo "🚀 Quick deploying Silent Connect..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
  echo "Please run as root: sudo $0"
  exit 1
fi

# Check if files exist
if [ ! -f "/var/www/html/.env" ]; then
  echo "❌ Files not uploaded yet. Please upload your files first:"
  echo "scp -r 'SilentConnect 2.4.4'/* root@104.236.102.224:/var/www/html/"
  exit 1
fi

# Run main deployment script
if [ -f "/var/www/html/deploy.sh" ]; then
  chmod +x /var/www/html/deploy.sh
  /var/www/html/deploy.sh
else
  echo "❌ deploy.sh not found. Please ensure all files are uploaded."
  exit 1
fi

echo ""
echo "🎉 Deployment complete!"
echo "🌐 Visit: http://104.236.102.224/setup.php to finish setup"
echo "🔐 Default admin: admin@silentconnect.com / Admin123!"
