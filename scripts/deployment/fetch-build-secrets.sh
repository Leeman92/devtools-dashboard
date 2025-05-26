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

# Function to safely fetch secrets
fetch_vault_secret() {
    local field="$1"
    local path="$2"
    local output
    
    output=$(vault kv get -field="$field" "$path" 2>&1)
    if [ $? -eq 0 ] && [ -n "$output" ] && [ "$output" != "null" ]; then
        echo "$output"
        return 0
    else
        echo "❌ Failed to fetch $field from $path: $output" >&2
        return 1
    fi
}

echo "📋 Fetching Harbor credentials..."

# Fetch Harbor credentials
HARBOR_USERNAME=$(fetch_vault_secret "HARBOR_USERNAME" "ci/github")
if [ $? -ne 0 ]; then
    echo "💥 Failed to fetch HARBOR_USERNAME - build cannot continue"
    exit 1
fi

HARBOR_PASSWORD=$(fetch_vault_secret "HARBOR_PASSWORD" "ci/github")
if [ $? -ne 0 ]; then
    echo "💥 Failed to fetch HARBOR_PASSWORD - build cannot continue"
    exit 1
fi

# Mask sensitive values
echo "::add-mask::$HARBOR_USERNAME"
echo "::add-mask::$HARBOR_PASSWORD"

# Export to GitHub environment
echo "HARBOR_USERNAME=$HARBOR_USERNAME" >> $GITHUB_ENV
echo "HARBOR_PASSWORD=$HARBOR_PASSWORD" >> $GITHUB_ENV

echo "✅ Harbor credentials retrieved and masked successfully" 