#!/bin/bash

# fetch-deploy-secrets.sh
# Script to fetch deployment secrets from Vault (server connection and SSH key)
# Usage: ./fetch-deploy-secrets.sh

set -euo pipefail

echo "🔐 Fetching deployment secrets from Vault..."

# Check required environment variables
if [[ -z "${VAULT_ADDR:-}" ]]; then
    echo "❌ VAULT_ADDR environment variable is required"
    exit 1
fi

if [[ -z "${VAULT_TOKEN:-}" ]]; then
    echo "❌ VAULT_TOKEN environment variable is required"
    exit 1
fi

# Function to safely fetch secrets (without echoing sensitive data)
fetch_vault_secret() {
    local field="$1"
    local path="$2"
    local temp_file
    local exit_code
    
    # Use temporary file to avoid any stdout leakage
    temp_file=$(mktemp)
    
    # Redirect all output to temp file
    vault kv get -field="$field" "$path" > "$temp_file" 2>&1
    exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        local output
        output=$(cat "$temp_file")
        if [ -n "$output" ] && [ "$output" != "null" ]; then
            # Store in global variable instead of echoing to prevent log exposure
            VAULT_SECRET_VALUE="$output"
            rm -f "$temp_file"
            return 0
        fi
    fi
    
    echo "❌ Failed to fetch $field from $path" >&2
    rm -f "$temp_file"
    return 1
}

echo "📋 Fetching server connection details..."

# Fetch server connection details
if fetch_vault_secret "SERVER_IP" "ci/github"; then
    SERVER_IP="$VAULT_SECRET_VALUE"
    echo "::add-mask::$SERVER_IP"
else
    echo "💥 Failed to fetch SERVER_IP - deployment cannot continue"
    exit 1
fi

if fetch_vault_secret "SERVER_PORT" "ci/github"; then
    SERVER_PORT="$VAULT_SECRET_VALUE"
    echo "::add-mask::$SERVER_PORT"
else
    echo "💥 Failed to fetch SERVER_PORT - deployment cannot continue"
    exit 1
fi

if fetch_vault_secret "SSH_PRIVATE_KEY" "ci/github"; then
    SSH_KEY="$VAULT_SECRET_VALUE"
    echo "::add-mask::$SSH_KEY"
else
    echo "💥 Failed to fetch SSH_PRIVATE_KEY - deployment cannot continue"
    exit 1
fi

# Export to GitHub environment (values already masked above)
echo "SERVER_IP=$SERVER_IP" >> $GITHUB_ENV
echo "SERVER_PORT=$SERVER_PORT" >> $GITHUB_ENV
printf "SSH_PRIVATE_KEY<<__EOT__\n%s\n__EOT__\n" "$SSH_KEY" >> "$GITHUB_ENV"

echo "✅ Deployment secrets retrieved and masked successfully" 