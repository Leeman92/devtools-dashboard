#!/bin/bash

# fetch-build-secrets.sh
# Script to fetch Harbor credentials from Vault for Docker build
# Usage: ./fetch-build-secrets.sh

set -euo pipefail

echo "🔐 Fetching build secrets from Vault..."

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

echo "📋 Fetching Harbor credentials..."

# Fetch Harbor credentials
if fetch_vault_secret "HARBOR_USERNAME" "ci/github"; then
    HARBOR_USERNAME="$VAULT_SECRET_VALUE"
    echo "::add-mask::$HARBOR_USERNAME"
else
    echo "💥 Failed to fetch HARBOR_USERNAME - build cannot continue"
    exit 1
fi

if fetch_vault_secret "HARBOR_PASSWORD" "ci/github"; then
    HARBOR_PASSWORD="$VAULT_SECRET_VALUE"
    echo "::add-mask::$HARBOR_PASSWORD"
else
    echo "💥 Failed to fetch HARBOR_PASSWORD - build cannot continue"
    exit 1
fi

# Export to GitHub environment
echo "HARBOR_USERNAME=$HARBOR_USERNAME" >> $GITHUB_ENV
echo "HARBOR_PASSWORD=$HARBOR_PASSWORD" >> $GITHUB_ENV

echo "✅ Harbor credentials retrieved and masked successfully" 