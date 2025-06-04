#!/bin/bash

# Build and push dependencies base image to Harbor
# Run this script when composer.json/composer.lock changes

set -e

echo "🔧 Building backend dependencies base image..."

# Build the dependencies image
docker build \
    -f backend/.docker/Dockerfile.deps \
    -t harbor.patricklehmann.dev/dashboard/backend-deps:latest \
    -t harbor.patricklehmann.dev/dashboard/backend-deps:$(date +%Y%m%d-%H%M%S) \
    backend/

echo "📤 Pushing dependencies image to Harbor..."

# Push both latest and timestamped versions
docker push harbor.patricklehmann.dev/dashboard/backend-deps:latest
docker push harbor.patricklehmann.dev/dashboard/backend-deps:$(date +%Y%m%d-%H%M%S)

echo "✅ Dependencies base image built and pushed successfully!"
echo ""
echo "📋 To use the optimized Dockerfile:"
echo "   1. Rename backend/.docker/Dockerfile to Dockerfile.old"
echo "   2. Rename backend/.docker/Dockerfile.optimized to Dockerfile"
echo "   3. Update docker-stack.yml to use the new build context"
echo ""
echo "⚡ Future builds will be much faster using the cached dependencies!" 