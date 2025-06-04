#!/bin/bash

# Smart dependency checking for pre-commit hook
# Only rebuilds base image if dependencies have meaningfully changed

set -e

# Configuration
DEPS_IMAGE="harbor.patricklehmann.dev/dashboard/backend-deps:latest"
DEPS_HASH_FILE=".docker-deps-hash"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Calculate hash of dependency files
calculate_deps_hash() {
    cat backend/composer.json backend/composer.lock backend/symfony.lock 2>/dev/null | sha256sum | cut -d' ' -f1
}

# Get current and previous dependency hashes
CURRENT_HASH=$(calculate_deps_hash)
PREVIOUS_HASH=""

if [ -f "$DEPS_HASH_FILE" ]; then
    PREVIOUS_HASH=$(cat "$DEPS_HASH_FILE")
fi

# Check if dependencies have actually changed
if [ "$CURRENT_HASH" != "$PREVIOUS_HASH" ]; then
    print_status $YELLOW "📦 Dependencies have changed (hash: ${CURRENT_HASH:0:12}...)"
    
    # Check if base image exists in Harbor
    if docker pull $DEPS_IMAGE > /dev/null 2>&1; then
        # Get image creation date
        IMAGE_DATE=$(docker inspect $DEPS_IMAGE --format='{{.Created}}' 2>/dev/null || echo "unknown")
        
        print_status $YELLOW "🔄 Base image exists but dependencies changed. Rebuilding..."
        echo "   Previous hash: ${PREVIOUS_HASH:0:12}..."
        echo "   Current hash:  ${CURRENT_HASH:0:12}..."
        echo "   Image date:    $IMAGE_DATE"
        
        if ./scripts/build-deps-image.sh; then
            # Update hash file on successful build
            echo "$CURRENT_HASH" > "$DEPS_HASH_FILE"
            print_status $GREEN "✅ Dependencies base image rebuilt and hash updated"
        else
            print_status $RED "❌ Failed to rebuild dependencies base image"
            exit 1
        fi
    else
        print_status $YELLOW "⚠️  Base image not found in Harbor. Building initial version..."
        if ./scripts/build-deps-image.sh; then
            echo "$CURRENT_HASH" > "$DEPS_HASH_FILE"
            print_status $GREEN "✅ Dependencies base image built and hash saved"
        else
            print_status $RED "❌ Failed to build dependencies base image"
            exit 1
        fi
    fi
else
    # Dependencies haven't changed, just verify image exists
    if docker pull $DEPS_IMAGE > /dev/null 2>&1; then
        print_status $GREEN "✅ Dependencies unchanged and base image available"
    else
        print_status $YELLOW "⚠️  Dependencies unchanged but base image missing. Rebuilding..."
        if ./scripts/build-deps-image.sh; then
            echo "$CURRENT_HASH" > "$DEPS_HASH_FILE"
            print_status $GREEN "✅ Dependencies base image rebuilt"
        else
            print_status $RED "❌ Failed to build dependencies base image"
            exit 1
        fi
    fi
fi

exit 0 