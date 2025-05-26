#!/bin/bash

# Get Docker group ID detection script
# This script detects the Docker group ID on the current system
# and exports it as DOCKER_GID environment variable

set -euo pipefail

# Function to get Docker group ID
get_docker_gid() {
    if command -v getent >/dev/null 2>&1; then
        # Use getent if available (most Linux systems)
        DOCKER_GID=$(getent group docker | cut -d: -f3)
    elif [ -f /etc/group ]; then
        # Fallback to parsing /etc/group directly
        DOCKER_GID=$(grep "^docker:" /etc/group | cut -d: -f3)
    else
        echo "Warning: Cannot detect Docker group ID, using default 999" >&2
        DOCKER_GID=999
    fi
    
    if [ -z "$DOCKER_GID" ]; then
        echo "Warning: Docker group not found, using default 999" >&2
        DOCKER_GID=999
    fi
    
    echo "$DOCKER_GID"
}

# Main execution
if [ "${1:-}" = "--export" ]; then
    # Export as environment variable
    export DOCKER_GID=$(get_docker_gid)
    echo "Exported DOCKER_GID=$DOCKER_GID"
elif [ "${1:-}" = "--env-file" ]; then
    # Write to .env file
    DOCKER_GID=$(get_docker_gid)
    if [ -f .env ]; then
        # Remove existing DOCKER_GID line if present
        sed -i '/^DOCKER_GID=/d' .env
    fi
    echo "DOCKER_GID=$DOCKER_GID" >> .env
    echo "Added DOCKER_GID=$DOCKER_GID to .env file"
else
    # Just print the GID
    get_docker_gid
fi 