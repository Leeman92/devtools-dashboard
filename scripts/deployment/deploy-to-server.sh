#!/bin/bash

# deploy-to-server.sh
# Script to deploy the dashboard application on the target server
# This script runs on the remote server via SSH
# Usage: ./deploy-to-server.sh

set -e

echo "🚀 Starting deployment on server..."

# Check if Vault is installed on the server and show version
if command -v vault &> /dev/null; then
    echo "Vault is installed on server. Version:"
    vault version
else
    echo "Vault is not installed on this server"
fi

# Initialize Docker Swarm if not already active
if ! docker info | grep -q "Swarm: active"; then
    echo "🔧 Initializing Docker Swarm..."
    docker swarm init
fi

# Set up network
echo "🌐 Setting up network..."
if ! docker network ls | grep -q dashboard-network; then
    docker network create --driver overlay dashboard-network
    echo "✅ Network created"
else
    echo "✅ Network already exists"
fi

# Set up environment configuration
echo "⚙️  Setting up environment configuration..."

# Create a versioned config name
CONFIG_VERSION=$(date +%Y%m%d_%H%M%S)
NEW_CONFIG_NAME="dashboard_env_${CONFIG_VERSION}"

# Create new config with versioned name
if [ -f "/home/patrick/dashboard/.env.production" ]; then
    docker config create "$NEW_CONFIG_NAME" /home/patrick/dashboard/.env.production
    echo "✅ Created new config: $NEW_CONFIG_NAME"
else
    echo "❌ Environment file not found!"
    exit 1
fi

# Update the docker-stack.yml to use the new config
sed "s/dashboard_env/$NEW_CONFIG_NAME/g" /home/patrick/dashboard/docker-stack.yml > /home/patrick/dashboard/docker-stack-updated.yml
echo "✅ Updated stack configuration to use new config"

# Clean up old images
echo "🧹 Cleaning up old images..."
docker image prune -f --filter "until=24h"

# Pull latest image
echo "📥 Pulling latest image..."
docker pull harbor.patricklehmann.dev/dashboard/dashboard:latest

# Deploy stack
echo "🚀 Deploying stack..."
cd /home/patrick/dashboard
docker stack deploy -c docker-stack-updated.yml dashboard

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
timeout=120
while [ $timeout -gt 0 ]; do
    if docker service ls | grep dashboard_dashboard | grep -q "2/2"; then
        echo "✅ All replicas are running!"
        break
    fi
    sleep 2
    timeout=$((timeout-2))
done

if [ $timeout -eq 0 ]; then
    echo "❌ Service deployment failed!"
    docker service logs dashboard_dashboard
    exit 1
fi

# Verify deployment
echo "🔍 Verifying deployment..."
sleep 10

if ! docker service ls | grep dashboard_dashboard | grep -q "2/2"; then
    echo "❌ Service health check failed!"
    docker service logs dashboard_dashboard
    exit 1
fi

# Check for unhealthy containers
unhealthy_containers=$(docker ps --filter "health=unhealthy" --filter "name=dashboard" --format "{{.Names}}")
if [ ! -z "$unhealthy_containers" ]; then
    echo "❌ Found unhealthy containers:"
    echo "$unhealthy_containers"
    docker service logs dashboard_dashboard
    exit 1
fi

echo "✅ Deployment completed successfully!"

# Clean up old configurations
echo "🧹 Cleaning up old configurations..."
# Remove old dashboard_env configs (keep only the 3 most recent)
OLD_CONFIGS=$(docker config ls --filter "name=dashboard_env_" --format "{{.Name}}" | sort -r | tail -n +4 || true)
if [ ! -z "$OLD_CONFIGS" ]; then
    echo "Removing old configs: $OLD_CONFIGS"
    echo "$OLD_CONFIGS" | xargs -r docker config rm
    echo "✅ Old configurations cleaned up"
else
    echo "✅ No old configurations to clean up"
fi

# Clean up temporary files
rm -f /home/patrick/dashboard/docker-stack-updated.yml
echo "✅ Temporary files cleaned up"

echo "🎉 Deployment process completed successfully!" 