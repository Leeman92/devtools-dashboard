#!/bin/bash

# Quick fix for network scope issue
# Run this on your production server

set -e

echo "🔧 Fixing network scope for Docker Swarm..."

# Stop MySQL container temporarily
echo "⏸️  Stopping MySQL container..."
docker stop dashboard-mysql

# Remove the existing local network
echo "🗑️  Removing existing local network..."
docker network rm dashboard-network

# Create new overlay network with attachable flag
echo "🌐 Creating new swarm overlay network..."
docker network create --driver overlay --attachable dashboard-network

# Recreate MySQL container to connect to new network
echo "🚀 Recreating MySQL container with new network..."
# Get the current MySQL configuration
MYSQL_IMAGE=$(docker inspect dashboard-mysql --format='{{.Config.Image}}')
MYSQL_VOLUME=$(docker inspect dashboard-mysql --format='{{range .Mounts}}{{if eq .Destination "/var/lib/mysql"}}{{.Name}}{{end}}{{end}}')
MYSQL_ENV=$(docker inspect dashboard-mysql --format='{{range .Config.Env}}{{println .}}{{end}}' | grep MYSQL_ROOT_PASSWORD)

# Remove the old container
docker rm dashboard-mysql

# Create new container with same configuration but new network
docker run -d \
    --name dashboard-mysql \
    --network dashboard-network \
    --restart unless-stopped \
    -v "$MYSQL_VOLUME:/var/lib/mysql" \
    -e "$MYSQL_ENV" \
    "$MYSQL_IMAGE" \
    --character-set-server=utf8mb4 \
    --collation-server=utf8mb4_unicode_ci \
    --innodb-buffer-pool-size=256M \
    --max-connections=100 \
    --query-cache-size=32M

# Verify network
echo "✅ Verifying network setup..."
docker network inspect dashboard-network | grep -E "(Scope|Driver)"

echo "🎉 Network fixed! You can now deploy your stack." 