#!/bin/bash

# Fix deployment port conflict
# Run this on your production server

set -e

echo "🔧 Fixing deployment port conflict..."

# Remove old stack if it exists
echo "🗑️  Removing old stack..."
docker stack rm dashboard || true

# Wait for services to be completely removed
echo "⏳ Waiting for old services to be removed..."
sleep 10

# Check if any services are still running
while docker service ls | grep -q dashboard; do
    echo "   Still waiting for services to be removed..."
    sleep 5
done

echo "✅ Old stack removed"

# Now redeploy
echo "🚀 Deploying fresh stack..."
cd /home/patrick/dashboard
docker stack deploy -c docker-stack-updated.yml dashboard

echo "🎉 Deployment should now succeed!" 