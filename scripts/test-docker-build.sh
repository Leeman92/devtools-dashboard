#!/bin/bash

# Script to test Docker build locally
# Usage: ./scripts/test-docker-build.sh [target]

set -e

TARGET="${1:-production}"
IMAGE_NAME="dashboard-test"

echo "🐳 Testing Docker build for target: $TARGET"
echo "📁 Build context: ./backend"
echo ""

# Check for buildx availability and preference
USE_BUILDX=false
BUILDX_AVAILABLE=false

# Check if buildx is available
if command -v docker >/dev/null 2>&1 && docker buildx version >/dev/null 2>&1; then
    BUILDX_AVAILABLE=true
    
    # Check if buildx has a builder instance configured
    if docker buildx ls | grep -q "default\|mybuilder" >/dev/null 2>&1; then
        USE_BUILDX=true
        echo "✅ Docker buildx available and configured - using buildx"
    else
        echo "⚠️  Docker buildx available but no builder configured"
        echo "   Run: docker buildx create --use --name mybuilder"
        echo "   Falling back to legacy builder"
    fi
else
    echo "ℹ️  Docker buildx not available - using legacy builder"
fi

# Clean up any existing test images
echo "🧹 Cleaning up existing test images..."
docker rmi "$IMAGE_NAME:$TARGET" 2>/dev/null || true

echo "🔨 Building Docker image..."

# Build with buildx (preferred) or legacy builder
if [ "$USE_BUILDX" = true ]; then
    # Check if current builder supports cache export
    CURRENT_BUILDER=$(docker buildx ls | grep '\*' | awk '{print $1}')
    BUILDER_DRIVER=$(docker buildx ls | grep '\*' | awk '{print $2}')
    
    if [ "$BUILDER_DRIVER" = "docker" ]; then
        echo "⚠️  Current builder uses 'docker' driver which doesn't support cache export"
        echo "   Creating a new builder with 'docker-container' driver..."
        
        # Create a new builder if it doesn't exist
        if ! docker buildx ls | grep -q "cache-builder"; then
            docker buildx create --name cache-builder --driver docker-container --use
            docker buildx inspect --bootstrap
        else
            docker buildx use cache-builder
        fi
        
        echo "✅ Switched to cache-builder with docker-container driver"
    fi
    
    echo "Command: docker buildx build -f backend/.docker/Dockerfile --target $TARGET -t $IMAGE_NAME:$TARGET ./backend"
    echo ""
    
    # Try with cache first, fall back without cache if it fails
    if docker buildx build \
      -f backend/.docker/Dockerfile \
      --target "$TARGET" \
      -t "$IMAGE_NAME:$TARGET" \
      --progress=plain \
      --load \
      --cache-from type=local,src=/tmp/.buildx-cache \
      --cache-to type=local,dest=/tmp/.buildx-cache-new,mode=max \
      ./backend 2>/dev/null; then
        
        # Move cache to avoid growing cache
        if [ -d "/tmp/.buildx-cache-new" ]; then
            rm -rf /tmp/.buildx-cache
            mv /tmp/.buildx-cache-new /tmp/.buildx-cache
        fi
        echo "✅ Build completed with caching"
    else
        echo "⚠️  Cache export failed, building without cache..."
        docker buildx build \
          -f backend/.docker/Dockerfile \
          --target "$TARGET" \
          -t "$IMAGE_NAME:$TARGET" \
          --progress=plain \
          --load \
          ./backend
        echo "✅ Build completed without caching"
    fi
else
    echo "Command: docker build -f backend/.docker/Dockerfile --target $TARGET -t $IMAGE_NAME:$TARGET ./backend"
    echo ""
    
    # Use legacy builder
    DOCKER_BUILDKIT=1 docker build \
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

# Show builder recommendations
echo ""
if [ "$USE_BUILDX" = true ]; then
    echo "✅ Using Docker buildx for optimal performance and caching"
elif [ "$BUILDX_AVAILABLE" = true ]; then
    echo "💡 Buildx is available but not configured. To enable:"
    echo "   docker buildx create --use --name mybuilder"
    echo "   docker buildx inspect --bootstrap"
else
    echo "💡 For better performance and caching, install Docker buildx:"
    echo "   # Arch Linux:"
    echo "   sudo pacman -S docker-buildx"
    echo "   # Or enable BuildKit:"
    echo "   export DOCKER_BUILDKIT=1"
fi 