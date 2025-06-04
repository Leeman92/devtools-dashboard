#!/bin/bash

# Build and push dependencies base image to Harbor
# Run this script when composer.json/composer.lock changes

set -e

echo "🔧 Building backend dependencies base image..."

# Generate timestamp once to ensure consistency
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
echo "📅 Building with timestamp: $TIMESTAMP"

# Build the dependencies image
docker build \
    -f backend/.docker/Dockerfile.deps \
    -t harbor.patricklehmann.dev/dashboard/backend-deps:latest \
    -t harbor.patricklehmann.dev/dashboard/backend-deps:$TIMESTAMP \
    backend/

echo "📤 Pushing dependencies image to Harbor..."

# Push latest version
docker push harbor.patricklehmann.dev/dashboard/backend-deps:latest

# Push timestamped version (should work now)
if docker push harbor.patricklehmann.dev/dashboard/backend-deps:$TIMESTAMP; then
    echo "✅ Timestamped version pushed: $TIMESTAMP"
else
    echo "⚠️  Timestamped push failed, but latest version is available"
fi

echo "✅ Dependencies base image built and pushed successfully!"
echo ""
echo "📋 To use the optimized Dockerfile:"
echo "   1. Rename backend/.docker/Dockerfile to Dockerfile.old"
echo "   2. Rename backend/.docker/Dockerfile.optimized to Dockerfile"
echo "   3. Update docker-stack.yml to use the new build context"
echo ""
echo "⚡ Future builds will be much faster using the cached dependencies!" 