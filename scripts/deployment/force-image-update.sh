#!/bin/bash

# force-image-update.sh
# Force Docker Swarm services to update with latest images
# This ensures services pull the newest version even with :latest tags

set -e

echo "🔄 Forcing Docker Swarm services to update with latest images..."

# Function to get image digest
get_image_digest() {
    local image=$1
    docker inspect --format='{{index .RepoDigests 0}}' "$image" 2>/dev/null || echo "$image"
}

# Pull latest images first
echo "📥 Pulling latest images..."
docker pull harbor.patricklehmann.dev/dashboard/backend:latest
docker pull harbor.patricklehmann.dev/dashboard/frontend:latest

# Get image digests for reliable updates
BACKEND_DIGEST=$(get_image_digest harbor.patricklehmann.dev/dashboard/backend:latest)
FRONTEND_DIGEST=$(get_image_digest harbor.patricklehmann.dev/dashboard/frontend:latest)

echo "Backend digest: $BACKEND_DIGEST"
echo "Frontend digest: $FRONTEND_DIGEST"

# Force update services with digests (more reliable than :latest)
echo "🚀 Updating backend service..."
if [ "$BACKEND_DIGEST" != "harbor.patricklehmann.dev/dashboard/backend:latest" ]; then
    docker service update --image "$BACKEND_DIGEST" dashboard_dashboard-backend
else
    # Fallback to force update with :latest
    docker service update --image harbor.patricklehmann.dev/dashboard/backend:latest dashboard_dashboard-backend --force
fi

echo "🚀 Updating frontend service..."
if [ "$FRONTEND_DIGEST" != "harbor.patricklehmann.dev/dashboard/frontend:latest" ]; then
    docker service update --image "$FRONTEND_DIGEST" dashboard_dashboard-frontend
else
    # Fallback to force update with :latest
    docker service update --image harbor.patricklehmann.dev/dashboard/frontend:latest dashboard_dashboard-frontend --force
fi

echo "✅ Service updates initiated!"
echo "💡 Use 'docker service ls' to monitor update progress" 