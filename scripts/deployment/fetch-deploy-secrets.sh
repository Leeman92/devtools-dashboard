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
    local output
    
    output=$(vault kv get -field="$field" "$path" 2>&1)
    if [ $? -eq 0 ] && [ -n "$output" ] && [ "$output" != "null" ]; then
        # Store in global variable instead of echoing to prevent log exposure
        VAULT_SECRET_VALUE="$output"
        return 0
    else
        echo "❌ Failed to fetch $field from $path: $output" >&2
        return 1
    fi
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