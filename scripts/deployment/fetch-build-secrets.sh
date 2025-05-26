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