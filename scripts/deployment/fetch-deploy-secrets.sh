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
    local temp_err
    local exit_code
    
    # Use temporary files to completely isolate vault output
    temp_file=$(mktemp)
    temp_err=$(mktemp)
    
    # Execute vault command with complete output isolation
    exec 3>&1 4>&2  # Save original stdout/stderr
    exec 1>"$temp_file" 2>"$temp_err"  # Redirect stdout/stderr to temp files
    
    vault kv get -field="$field" "$path"
    exit_code=$?
    
    exec 1>&3 2>&4  # Restore original stdout/stderr
    exec 3>&- 4>&-  # Close the saved descriptors
    
    if [ $exit_code -eq 0 ]; then
        local output
        output=$(cat "$temp_file" 2>/dev/null)
        if [ -n "$output" ] && [ "$output" != "null" ]; then
            # Store in global variable instead of echoing to prevent log exposure
            VAULT_SECRET_VALUE="$output"
            rm -f "$temp_file" "$temp_err"
            return 0
        fi
    fi
    
    echo "❌ Failed to fetch $field from $path" >&2
    rm -f "$temp_file" "$temp_err"
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

echo "SMALL DEBUG STATEMENT BY ME"
# Export to GitHub environment (values already masked above)
echo "SERVER_IP=$SERVER_IP" >> $GITHUB_ENV
echo "SERVER_PORT=$SERVER_PORT" >> $GITHUB_ENV
printf "SSH_PRIVATE_KEY<<__EOT__\n%s\n__EOT__\n" "$SSH_KEY" >> "$GITHUB_ENV"

echo "✅ Deployment secrets retrieved and masked successfully" 