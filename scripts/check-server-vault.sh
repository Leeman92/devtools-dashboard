#!/bin/bash

# Script to check Vault version on your server
# Usage: ./scripts/check-server-vault.sh

set -e

# You can customize these variables or pass them as environment variables
SERVER_IP="${SERVER_IP:-your-server-ip}"
SERVER_PORT="${SERVER_PORT:-22}"
SERVER_USER="${SERVER_USER:-patrick}"

if [ "$SERVER_IP" = "your-server-ip" ]; then
    echo "Please set your server details:"
    echo "  export SERVER_IP=your.server.ip"
    echo "  export SERVER_PORT=22"
    echo "  export SERVER_USER=patrick"
    echo "Then run this script again."
    exit 1
fi

echo "Checking Vault version on server $SERVER_IP..."

# Check Vault version on server
ssh -p "$SERVER_PORT" "$SERVER_USER@$SERVER_IP" '
    echo "=== Server Information ==="
    echo "Hostname: $(hostname)"
    echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2 | tr -d \")"
    echo ""
    
    echo "=== Vault Status ==="
    if command -v vault &> /dev/null; then
        echo "✅ Vault is installed"
        echo "Version: $(vault version)"
        echo ""
        
        # Check if Vault server is running
        if pgrep -f "vault server" > /dev/null; then
            echo "✅ Vault server process is running"
        else
            echo "❌ Vault server process is not running"
        fi
        
        # Check Vault status (if accessible)
        if vault status &> /dev/null; then
            echo "✅ Vault server is accessible"
            vault status
        else
            echo "❌ Vault server is not accessible or sealed"
        fi
    else
        echo "❌ Vault is not installed on this server"
    fi
    
    echo ""
    echo "=== Docker Status ==="
    if command -v docker &> /dev/null; then
        echo "✅ Docker is installed"
        echo "Version: $(docker --version)"
        
        # Check if Docker is running
        if docker info &> /dev/null; then
            echo "✅ Docker daemon is running"
            
            # Check Docker Swarm status
            if docker info | grep -q "Swarm: active"; then
                echo "✅ Docker Swarm is active"
            else
                echo "❌ Docker Swarm is not active"
            fi
        else
            echo "❌ Docker daemon is not running"
        fi
    else
        echo "❌ Docker is not installed"
    fi
'

echo ""
echo "✅ Server check completed!" 