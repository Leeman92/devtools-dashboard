#!/bin/bash

# reset-mysql.sh
# Script to cleanly reset MySQL setup when there are authentication issues
# Usage: ./reset-mysql.sh

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

print_status $BLUE "🔄 Resetting MySQL setup for DevTools Dashboard"
echo ""

# Configuration
MYSQL_CONTAINER_NAME="dashboard-mysql"
MYSQL_DATA_DIR="/var/lib/mysql-dashboard"
NETWORK_NAME="dashboard-network"

print_status $YELLOW "⚠️  This will completely reset your MySQL setup and DELETE ALL DATA!"
read -p "   Are you sure you want to continue? (y/N): " confirm

if [[ "${confirm,,}" != "y" ]]; then
    print_status $BLUE "   Operation cancelled"
    exit 0
fi

# Stop and remove container
print_status $YELLOW "🛑 Stopping and removing MySQL container..."
docker stop "$MYSQL_CONTAINER_NAME" 2>/dev/null || true
docker rm "$MYSQL_CONTAINER_NAME" 2>/dev/null || true
print_status $GREEN "✅ Container removed"

# Remove data directory
print_status $YELLOW "🗑️  Removing MySQL data directory..."
if [ -d "$MYSQL_DATA_DIR" ]; then
    sudo rm -rf "$MYSQL_DATA_DIR"
    print_status $GREEN "✅ Data directory removed: $MYSQL_DATA_DIR"
else
    print_status $BLUE "   Data directory doesn't exist: $MYSQL_DATA_DIR"
fi

# Check network
print_status $YELLOW "🌐 Checking Docker network..."
if docker network ls | grep -q "$NETWORK_NAME"; then
    # Check if network is attachable
    if docker info | grep -q "Swarm: active"; then
        NETWORK_ATTACHABLE=$(docker network inspect "$NETWORK_NAME" --format '{{.Attachable}}' 2>/dev/null || echo "false")
        if [[ "$NETWORK_ATTACHABLE" != "true" ]]; then
            print_status $YELLOW "⚠️  Network exists but is not attachable, removing..."
            docker network rm "$NETWORK_NAME" 2>/dev/null || true
            print_status $GREEN "✅ Non-attachable network removed"
        else
            print_status $GREEN "✅ Network exists and is attachable"
        fi
    else
        print_status $GREEN "✅ Network exists"
    fi
else
    print_status $BLUE "   Network doesn't exist (will be created by setup script)"
fi

print_status $GREEN "🎉 MySQL reset completed successfully!"
echo ""

print_status $BLUE "📋 Next steps:"
echo "   1. Run the MySQL setup script: ./scripts/deployment/setup-standalone-mysql.sh production"
echo "   2. The script will create fresh MySQL installation with new credentials"
echo "   3. All passwords will be automatically stored in Vault"
echo ""

print_status $YELLOW "⚠️  Remember: All previous MySQL data has been deleted!"
print_status $GREEN "✅ Ready for fresh MySQL setup!" 