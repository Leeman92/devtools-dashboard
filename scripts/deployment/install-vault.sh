#!/bin/bash

# install-vault.sh
# Script to install HashiCorp Vault CLI
# Usage: ./install-vault.sh [version] [sha256]

set -euo pipefail

# Default values (can be overridden by environment variables or parameters)
VAULT_VERSION="${1:-${VAULT_VERSION:-1.19.4}}"
VAULT_SHA256="${2:-${VAULT_SHA256:-d8621f31427ecb6712923fc2db207b3b3c04711b722b11f34627cd4cf837a9c6}}"

echo "🔧 Installing Vault CLI version $VAULT_VERSION..."

# Download Vault
echo "📥 Downloading Vault CLI..."
curl -fsSL "https://releases.hashicorp.com/vault/$VAULT_VERSION/vault_${VAULT_VERSION}_linux_amd64.zip" -o vault.zip

# Verify checksum
echo "🔍 Verifying checksum..."
echo "$VAULT_SHA256  vault.zip" | sha256sum -c

# Extract and install
echo "📦 Extracting and installing..."
unzip vault.zip
sudo mv vault /usr/local/bin/vault

# Clean up
rm vault.zip

# Verify installation
echo "✅ Vault CLI installed successfully!"
echo "Installed Vault version:"
vault version 