#!/bin/bash

# deploy-to-server.sh
# Script to deploy the dashboard application on the target server
# This script runs on the remote server via SSH
# Usage: ./deploy-to-server.sh

set -e

echo "🚀 Starting deployment on server..."

# Load deployment info to know what changed
if [ -f "/home/patrick/dashboard/deployment-info.env" ]; then
    source /home/patrick/dashboard/deployment-info.env
    echo "📋 Deployment info loaded:"
    echo "   Backend changed: $BACKEND_CHANGED"
    echo "   Frontend changed: $FRONTEND_CHANGED"
else
    echo "⚠️  No deployment info found, will update all services"
    BACKEND_CHANGED="true"
    FRONTEND_CHANGED="true"
fi

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

# Pull latest images (only for changed services)
echo "📥 Pulling latest images..."
if [ "$BACKEND_CHANGED" = "true" ]; then
    echo "   Pulling backend image..."
    docker pull harbor.patricklehmann.dev/dashboard/backend:latest
else
    echo "   Skipping backend image (no changes)"
fi

if [ "$FRONTEND_CHANGED" = "true" ]; then
    echo "   Pulling frontend image..."
    docker pull harbor.patricklehmann.dev/dashboard/frontend:latest
else
    echo "   Skipping frontend image (no changes)"
fi

# Deploy stack
echo "🚀 Deploying stack..."
cd /home/patrick/dashboard
docker stack deploy -c docker-stack-updated.yml dashboard

# Force service updates to use new images (only for changed services)
echo "🔄 Forcing service updates to pull new images..."
if [ "$BACKEND_CHANGED" = "true" ]; then
    echo "   Updating backend service..."
    docker service update --image harbor.patricklehmann.dev/dashboard/backend:latest dashboard_dashboard-backend --force
else
    echo "   Skipping backend service update (no changes)"
fi

if [ "$FRONTEND_CHANGED" = "true" ]; then
    echo "   Updating frontend service..."
    docker service update --image harbor.patricklehmann.dev/dashboard/frontend:latest dashboard_dashboard-frontend --force
else
    echo "   Skipping frontend service update (no changes)"
fi

# Wait for services to be ready (only check updated services)
echo "⏳ Waiting for updated services to be ready..."
timeout=120
while [ $timeout -gt 0 ]; do
    all_ready=true
    
    if [ "$BACKEND_CHANGED" = "true" ]; then
        backend_ready=$(docker service ls | grep dashboard_dashboard-backend | grep -c "2/2" || echo "0")
        if [ "$backend_ready" != "1" ]; then
            all_ready=false
            echo "   Backend: $backend_ready/1 ready"
        fi
    fi
    
    if [ "$FRONTEND_CHANGED" = "true" ]; then
        frontend_ready=$(docker service ls | grep dashboard_dashboard-frontend | grep -c "2/2" || echo "0")
        if [ "$frontend_ready" != "1" ]; then
            all_ready=false
            echo "   Frontend: $frontend_ready/1 ready"
        fi
    fi
    
    if [ "$all_ready" = "true" ]; then
        echo "✅ All updated services are running!"
        break
    fi
    
    sleep 5
    timeout=$((timeout-5))
done

if [ $timeout -eq 0 ]; then
    echo "❌ Service deployment failed!"
    if [ "$BACKEND_CHANGED" = "true" ]; then
        echo "Backend logs:"
        docker service logs dashboard_dashboard-backend --tail 20
    fi
    if [ "$FRONTEND_CHANGED" = "true" ]; then
        echo "Frontend logs:"
        docker service logs dashboard_dashboard-frontend --tail 20
    fi
    exit 1
fi

# Verify deployment (only check updated services)
echo "🔍 Verifying deployment..."
sleep 10

verification_failed=false

if [ "$BACKEND_CHANGED" = "true" ]; then
    backend_ready=$(docker service ls | grep dashboard_dashboard-backend | grep -c "2/2" || echo "0")
    if [ "$backend_ready" != "1" ]; then
        echo "❌ Backend service health check failed!"
        echo "Backend ready: $backend_ready"
        echo "Backend logs:"
        docker service logs dashboard_dashboard-backend --tail 20
        verification_failed=true
    else
        echo "✅ Backend service is healthy"
    fi
fi

if [ "$FRONTEND_CHANGED" = "true" ]; then
    frontend_ready=$(docker service ls | grep dashboard_dashboard-frontend | grep -c "2/2" || echo "0")
    if [ "$frontend_ready" != "1" ]; then
        echo "❌ Frontend service health check failed!"
        echo "Frontend ready: $frontend_ready"
        echo "Frontend logs:"
        docker service logs dashboard_dashboard-frontend --tail 20
        verification_failed=true
    else
        echo "✅ Frontend service is healthy"
    fi
fi

if [ "$verification_failed" = "true" ]; then
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
rm -f /home/patrick/dashboard/deployment-info.env
echo "✅ Temporary files cleaned up"

echo "🎉 Deployment process completed successfully!" 