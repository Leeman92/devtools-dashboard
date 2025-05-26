#!/bin/bash

# Script to update Vault version in GitHub Actions workflow
# Usage: ./scripts/update-vault-version.sh <version>

set -e

if [ $# -eq 0 ]; then
    echo "Usage: $0 <vault-version>"
    echo "Example: $0 1.19.4"
    exit 1
fi

NEW_VERSION="$1"
WORKFLOW_FILE=".github/workflows/deploy.yml"

echo "Updating Vault version to $NEW_VERSION..."

# Get the SHA256 checksum for the new version
echo "Fetching SHA256 checksum for Vault $NEW_VERSION..."
SHA256=$(curl -s "https://releases.hashicorp.com/vault/$NEW_VERSION/vault_${NEW_VERSION}_SHA256SUMS" | grep "linux_amd64.zip" | cut -d' ' -f1)

if [ -z "$SHA256" ]; then
    echo "Error: Could not fetch SHA256 checksum for Vault $NEW_VERSION"
    exit 1
fi

echo "SHA256 checksum: $SHA256"

# Update the workflow file
if [ ! -f "$WORKFLOW_FILE" ]; then
    echo "Error: Workflow file $WORKFLOW_FILE not found"
    exit 1
fi

# Create backup
cp "$WORKFLOW_FILE" "${WORKFLOW_FILE}.backup"

# Update version and SHA256 in the workflow file
sed -i "s/VAULT_VERSION: \".*\"/VAULT_VERSION: \"$NEW_VERSION\"/" "$WORKFLOW_FILE"
sed -i "s/VAULT_SHA256: \".*\"/VAULT_SHA256: \"$SHA256\"/" "$WORKFLOW_FILE"

echo "✅ Updated Vault version to $NEW_VERSION in $WORKFLOW_FILE"
echo "✅ Updated SHA256 checksum to $SHA256"
echo "📁 Backup created at ${WORKFLOW_FILE}.backup"

# Show the changes
echo ""
echo "Changes made:"
grep -A 2 "env:" "$WORKFLOW_FILE" | head -3 