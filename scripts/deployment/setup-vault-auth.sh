#!/bin/bash

# setup-vault-auth.sh
# Script to handle Vault authentication using GitHub OIDC
# Usage: ./setup-vault-auth.sh

set -euo pipefail

echo "🔐 Setting up Vault authentication..."

# Check required environment variables
if [[ -z "${VAULT_ADDR:-}" ]]; then
    echo "❌ VAULT_ADDR environment variable is required"
    exit 1
fi

if [[ -z "${ACTIONS_ID_TOKEN_REQUEST_TOKEN:-}" ]]; then
    echo "❌ ACTIONS_ID_TOKEN_REQUEST_TOKEN is required (GitHub OIDC)"
    exit 1
fi

if [[ -z "${ACTIONS_ID_TOKEN_REQUEST_URL:-}" ]]; then
    echo "❌ ACTIONS_ID_TOKEN_REQUEST_URL is required (GitHub OIDC)"
    exit 1
fi

# Get GitHub OIDC token
echo "📋 Fetching OIDC token from GitHub..."
OIDC_TOKEN=$(curl -s -H "Authorization: bearer $ACTIONS_ID_TOKEN_REQUEST_TOKEN" \
    "$ACTIONS_ID_TOKEN_REQUEST_URL" | jq -r '.value')

if [[ -z "$OIDC_TOKEN" || "$OIDC_TOKEN" == "null" ]]; then
    echo "❌ Failed to fetch OIDC token"
    exit 1
fi

echo "::add-mask::$OIDC_TOKEN"
echo "OIDC_TOKEN=$OIDC_TOKEN" >> $GITHUB_ENV

# Authenticate with Vault using OIDC
echo "🔑 Authenticating with Vault..."
LOGIN_RESPONSE=$(echo "{\"jwt\": \"$OIDC_TOKEN\", \"role\": \"github-actions\"}" | \
    curl -s --request POST --data @- "$VAULT_ADDR/v1/auth/jwt/login")

echo "$LOGIN_RESPONSE"

VAULT_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.auth.client_token')

if [[ "$VAULT_TOKEN" == "null" || -z "$VAULT_TOKEN" ]]; then
    echo "❌ Vault authentication failed"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
fi

echo "::add-mask::$VAULT_TOKEN"
echo "VAULT_TOKEN=$VAULT_TOKEN" >> $GITHUB_ENV

echo "✅ Vault authentication successful" 