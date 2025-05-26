#!/bin/bash

# Script to test Docker build locally
# Usage: ./scripts/test-docker-build.sh [target]

set -e

TARGET="${1:-production}"
IMAGE_NAME="dashboard-test"

echo "🐳 Testing Docker build for target: $TARGET"
echo "📁 Build context: ./backend"
echo ""

# Check Docker builder capabilities
DOCKER_BUILDKIT_AVAILABLE=false
if docker buildx version >/dev/null 2>&1; then
    DOCKER_BUILDKIT_AVAILABLE=true
    echo "✅ Docker BuildKit available"
else
    echo "ℹ️  Using legacy Docker builder"
fi

# Clean up any existing test images
echo "🧹 Cleaning up existing test images..."
docker rmi "$IMAGE_NAME:$TARGET" 2>/dev/null || true

echo "🔨 Building Docker image..."

# Build with appropriate flags based on Docker capabilities
if [ "$DOCKER_BUILDKIT_AVAILABLE" = true ]; then
    echo "Command: docker buildx build -f backend/.docker/Dockerfile --target $TARGET -t $IMAGE_NAME:$TARGET ./backend"
    echo ""
    
    # Use BuildKit with progress
    docker buildx build \
      -f backend/.docker/Dockerfile \
      --target "$TARGET" \
      -t "$IMAGE_NAME:$TARGET" \
      --progress=plain \
      --load \
      ./backend
else
    echo "Command: docker build -f backend/.docker/Dockerfile --target $TARGET -t $IMAGE_NAME:$TARGET ./backend"
    echo ""
    
    # Use legacy builder without progress flag
    docker build \
      -f backend/.docker/Dockerfile \
      --target "$TARGET" \
      -t "$IMAGE_NAME:$TARGET" \
      ./backend
fi

echo ""
echo "✅ Build completed successfully!"
echo ""

# Test the image
echo "🧪 Testing the built image..."
echo "Testing PHP version..."
docker run --rm "$IMAGE_NAME:$TARGET" php --version

echo ""
echo "Testing Composer version..."
docker run --rm "$IMAGE_NAME:$TARGET" composer --version

echo ""
echo "Testing application structure..."
docker run --rm "$IMAGE_NAME:$TARGET" ls -la /app

echo ""
echo "📋 Image details:"
docker images "$IMAGE_NAME:$TARGET"

echo ""
echo "🎉 All tests passed! Image is ready."
echo ""
echo "To run the container:"
echo "  docker run -p 8080:80 $IMAGE_NAME:$TARGET"
echo ""
echo "To inspect the container:"
echo "  docker run -it --rm $IMAGE_NAME:$TARGET sh"
echo ""
echo "To clean up:"
echo "  docker rmi $IMAGE_NAME:$TARGET"

# Optional: Show Docker builder recommendation
if [ "$DOCKER_BUILDKIT_AVAILABLE" = false ]; then
    echo ""
    echo "💡 Recommendation: Install Docker BuildKit for better performance:"
    echo "   https://docs.docker.com/buildx/working-with-buildx/"
fi 