#!/bin/bash

# Script to deploy nginx configuration for dashboard.patricklehmann.dev
# Usage: ./scripts/deploy-nginx-config.sh

set -e

NGINX_CONFIG_FILE="/etc/nginx/sites-available/dashboard.patricklehmann.dev.conf"
NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
SITE_NAME="dashboard.patricklehmann.dev"

echo "🌐 Deploying nginx configuration for $SITE_NAME"
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script needs to be run as root or with sudo"
    echo "   Usage: sudo ./scripts/deploy-nginx-config.sh"
    exit 1
fi

# Check if nginx is installed
if ! command -v nginx >/dev/null 2>&1; then
    echo "❌ Nginx is not installed. Please install nginx first:"
    echo "   sudo apt update && sudo apt install nginx"
    exit 1
fi

# Check if config file exists
if [ ! -f "$NGINX_CONFIG_FILE" ]; then
    echo "❌ Nginx config file not found: $NGINX_CONFIG_FILE"
    echo "   Make sure you're running this from the project root directory"
    exit 1
fi

# Create nginx directories if they don't exist
mkdir -p "$NGINX_SITES_AVAILABLE"
mkdir -p "$NGINX_SITES_ENABLED"

# Copy configuration file
echo "📋 Copying nginx configuration..."
cp "$NGINX_CONFIG_FILE" "$NGINX_SITES_AVAILABLE/$SITE_NAME"
echo "✅ Configuration copied to $NGINX_SITES_AVAILABLE/$SITE_NAME"

# Create symbolic link to enable site
if [ -L "$NGINX_SITES_ENABLED/$SITE_NAME" ]; then
    echo "ℹ️  Site already enabled, updating link..."
    rm "$NGINX_SITES_ENABLED/$SITE_NAME"
fi

ln -s "$NGINX_SITES_AVAILABLE/$SITE_NAME" "$NGINX_SITES_ENABLED/$SITE_NAME"
echo "✅ Site enabled: $NGINX_SITES_ENABLED/$SITE_NAME"

# Test nginx configuration
echo "🧪 Testing nginx configuration..."
if nginx -t; then
    echo "✅ Nginx configuration test passed"
else
    echo "❌ Nginx configuration test failed"
    echo "   Removing invalid configuration..."
    rm -f "$NGINX_SITES_ENABLED/$SITE_NAME"
    exit 1
fi

# Reload nginx
echo "🔄 Reloading nginx..."
systemctl reload nginx
echo "✅ Nginx reloaded successfully"

echo ""
echo "🎉 Nginx configuration deployed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Make sure your Docker Swarm service is running on port 3001"
echo "2. Test the configuration: curl -H 'Host: dashboard.patricklehmann.dev' http://localhost"
echo "3. Set up SSL with certbot:"
echo "   sudo certbot --nginx -d dashboard.patricklehmann.dev"
echo ""
echo "📊 Useful commands:"
echo "   Check nginx status: sudo systemctl status nginx"
echo "   View nginx logs: sudo tail -f /var/log/nginx/dashboard.patricklehmann.dev.*.log"
echo "   Test configuration: sudo nginx -t"
echo "   Reload nginx: sudo systemctl reload nginx" 