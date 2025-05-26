#!/bin/bash

# setup-vault-secrets.sh
# Script to set up all required secrets in HashiCorp Vault for DevTools Dashboard
# Usage: ./setup-vault-secrets.sh [environment]

set -euo pipefail

ENVIRONMENT="${1:-production}"
VAULT_PATH="secret/dashboard/${ENVIRONMENT}"

echo "🔐 Setting up HashiCorp Vault secrets for DevTools Dashboard"
echo "📍 Environment: ${ENVIRONMENT}"
echo "📂 Vault path: ${VAULT_PATH}"
echo ""

# Check required environment variables
if [[ -z "${VAULT_ADDR:-}" ]]; then
    echo "❌ VAULT_ADDR environment variable is required"
    echo "   Example: export VAULT_ADDR=https://vault.patricklehmann.dev"
    exit 1
fi

if [[ -z "${VAULT_TOKEN:-}" ]]; then
    echo "❌ VAULT_TOKEN environment variable is required"
    echo "   Example: export VAULT_TOKEN=hvs.your_token_here"
    exit 1
fi

echo "✅ Vault configuration:"
echo "   Address: ${VAULT_ADDR}"
echo "   Token: ${VAULT_TOKEN:0:10}..."
echo ""

# Function to prompt for secret value
prompt_secret() {
    local var_name="$1"
    local description="$2"
    local default_value="${3:-}"
    local value
    
    echo "🔑 Setting up: ${var_name}"
    echo "   Description: ${description}"
    
    if [[ -n "$default_value" ]]; then
        echo "   Default: ${default_value}"
        read -p "   Enter value (or press Enter for default): " -s value
        echo ""
        if [[ -z "$value" ]]; then
            value="$default_value"
        fi
    else
        read -p "   Enter value: " -s value
        echo ""
        if [[ -z "$value" ]]; then
            echo "   ❌ Value cannot be empty"
            return 1
        fi
    fi
    
    echo "$value"
}

# Function to generate random secret
generate_secret() {
    openssl rand -hex 32
}

echo "📋 Required secrets for DevTools Dashboard:"
echo ""

# Collect all secrets
declare -A SECRETS

# APP_SECRET
echo "1. Application Secret (APP_SECRET)"
if SECRET_VALUE=$(prompt_secret "APP_SECRET" "Symfony application secret key (32 characters)" "$(generate_secret)"); then
    SECRETS["APP_SECRET"]="$SECRET_VALUE"
else
    echo "❌ Failed to set APP_SECRET"
    exit 1
fi
echo ""

# DATABASE_URL
echo "2. Database Connection (DATABASE_URL)"
if SECRET_VALUE=$(prompt_secret "DATABASE_URL" "MariaDB connection string" "mysql://dashboard_user:dashboard_password@mariadb:3306/dashboard"); then
    SECRETS["DATABASE_URL"]="$SECRET_VALUE"
else
    echo "❌ Failed to set DATABASE_URL"
    exit 1
fi
echo ""

# DOCKER_SOCKET_PATH
echo "3. Docker Socket Path (DOCKER_SOCKET_PATH)"
if SECRET_VALUE=$(prompt_secret "DOCKER_SOCKET_PATH" "Path to Docker socket" "/var/run/docker.sock"); then
    SECRETS["DOCKER_SOCKET_PATH"]="$SECRET_VALUE"
else
    echo "❌ Failed to set DOCKER_SOCKET_PATH"
    exit 1
fi
echo ""

# GITHUB_TOKEN
echo "4. GitHub Personal Access Token (GITHUB_TOKEN)"
if SECRET_VALUE=$(prompt_secret "GITHUB_TOKEN" "GitHub PAT with repo and actions:read permissions" ""); then
    SECRETS["GITHUB_TOKEN"]="$SECRET_VALUE"
else
    echo "❌ Failed to set GITHUB_TOKEN"
    exit 1
fi
echo ""

# GITHUB_API_URL
echo "5. GitHub API URL (GITHUB_API_URL)"
if SECRET_VALUE=$(prompt_secret "GITHUB_API_URL" "GitHub API base URL" "https://api.github.com"); then
    SECRETS["GITHUB_API_URL"]="$SECRET_VALUE"
else
    echo "❌ Failed to set GITHUB_API_URL"
    exit 1
fi
echo ""

# PROMETHEUS_URL
echo "6. Prometheus URL (PROMETHEUS_URL)"
if SECRET_VALUE=$(prompt_secret "PROMETHEUS_URL" "Prometheus server URL" "http://prometheus:9090"); then
    SECRETS["PROMETHEUS_URL"]="$SECRET_VALUE"
else
    echo "❌ Failed to set PROMETHEUS_URL"
    exit 1
fi
echo ""

# GRAFANA_URL
echo "7. Grafana URL (GRAFANA_URL)"
if SECRET_VALUE=$(prompt_secret "GRAFANA_URL" "Grafana server URL" "http://grafana:3000"); then
    SECRETS["GRAFANA_URL"]="$SECRET_VALUE"
else
    echo "❌ Failed to set GRAFANA_URL"
    exit 1
fi
echo ""

# Store all secrets in Vault
echo "💾 Storing secrets in HashiCorp Vault..."
echo "📂 Path: ${VAULT_PATH}"
echo ""

# Build the vault command
VAULT_CMD="vault kv put ${VAULT_PATH}"
for key in "${!SECRETS[@]}"; do
    VAULT_CMD="${VAULT_CMD} ${key}=\"${SECRETS[$key]}\""
done

# Execute the vault command
if eval "$VAULT_CMD" > /dev/null 2>&1; then
    echo "✅ All secrets stored successfully in Vault"
else
    echo "❌ Failed to store secrets in Vault"
    echo "   Check your Vault connection and permissions"
    exit 1
fi

echo ""
echo "🔍 Verifying stored secrets..."

# Verify each secret
for key in "${!SECRETS[@]}"; do
    if vault kv get -field="$key" "$VAULT_PATH" > /dev/null 2>&1; then
        echo "✅ $key verified"
    else
        echo "❌ $key verification failed"
    fi
done

echo ""
echo "🎉 Vault setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Test environment generation: ./scripts/deployment/generate-env-file.sh"
echo "   2. Deploy the application with the generated environment file"
echo "   3. Verify all services can access their required secrets"
echo ""
echo "🔐 Security reminders:"
echo "   - Rotate secrets regularly"
echo "   - Use different secrets for each environment"
echo "   - Monitor Vault access logs"
echo "   - Backup Vault data securely" 