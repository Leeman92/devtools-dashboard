#!/bin/bash

# Check if composer dependencies have changed and rebuild base image if needed
# This can be used in CI/CD to automatically rebuild the deps image

set -e

DEPS_IMAGE="harbor.patricklehmann.dev/dashboard/backend-deps:latest"
COMPOSER_FILES="backend/composer.json backend/composer.lock backend/symfony.lock"

echo "🔍 Checking if PHP dependencies have changed..."

# Check if any composer files have been modified in recent commits
if git diff --name-only HEAD~1 HEAD | grep -E "(composer\.(json|lock)|symfony\.lock)" > /dev/null; then
    echo "📦 Dependencies have changed! Rebuilding base image..."
    ./scripts/build-deps-image.sh
else
    echo "✅ No dependency changes detected. Using existing base image."
    
    # Verify the base image exists in Harbor
    if docker pull $DEPS_IMAGE > /dev/null 2>&1; then
        echo "✅ Base image found in Harbor registry."
    else
        echo "⚠️  Base image not found in Harbor. Building initial version..."
        ./scripts/build-deps-image.sh
    fi
fi

echo "🚀 Ready to build optimized application image!" 