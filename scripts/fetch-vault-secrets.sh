#!/bin/bash

# Script to fetch secrets from HashiCorp Vault and generate .env.prod file
# Usage: ./scripts/fetch-vault-secrets.sh [environment]

set -e

ENVIRONMENT="${1:-production}"
VAULT_PATH="secret/dashboard/${ENVIRONMENT}"
ENV_FILE="backend/.env.${ENVIRONMENT}"
ENV_TEMPLATE="backend/.env.template"

echo "🔐 Fetching secrets from HashiCorp Vault"
echo "📍 Vault path: ${VAULT_PATH}"
echo "📄 Output file: ${ENV_FILE}"
echo ""

# Check if Vault CLI is available
if ! command -v vault >/dev/null 2>&1; then
    echo "❌ Vault CLI not found. Please install HashiCorp Vault CLI"
    echo "   https://developer.hashicorp.com/vault/downloads"
    exit 1
fi

# Check if Vault is accessible
if ! vault status >/dev/null 2>&1; then
    echo "❌ Cannot connect to Vault. Please check:"
    echo "   - VAULT_ADDR environment variable is set"
    echo "   - VAULT_TOKEN environment variable is set"
    echo "   - Vault server is accessible"
    echo ""
    echo "Current Vault configuration:"
    echo "   VAULT_ADDR: ${VAULT_ADDR:-'Not set'}"
    echo "   VAULT_TOKEN: ${VAULT_TOKEN:+Set (hidden)}"
    exit 1
fi

# Check if authenticated
if ! vault auth -method=token >/dev/null 2>&1; then
    echo "❌ Not authenticated with Vault. Please set VAULT_TOKEN"
    exit 1
fi

echo "✅ Vault connection verified"
echo ""

# Create backup of existing env file
if [ -f "$ENV_FILE" ]; then
    echo "📋 Creating backup of existing environment file..."
    cp "$ENV_FILE" "${ENV_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Start building the environment file
echo "🔨 Building environment file..."

# Write header
cat > "$ENV_FILE" << EOF
# Environment file generated from HashiCorp Vault
# Generated on: $(date -u '+%Y-%m-%d %H:%M:%S UTC')
# Vault path: ${VAULT_PATH}
# Environment: ${ENVIRONMENT}

# Symfony Environment
APP_ENV=${ENVIRONMENT}
APP_DEBUG=false

EOF

# Function to safely fetch and write secret
fetch_secret() {
    local key="$1"
    local vault_key="${2:-$key}"
    local required="${3:-true}"
    
    echo "  Fetching ${key}..."
    
    if value=$(vault kv get -field="$vault_key" "$VAULT_PATH" 2>/dev/null); then
        echo "${key}=${value}" >> "$ENV_FILE"
        echo "    ✅ ${key} retrieved"
    else
        if [ "$required" = "true" ]; then
            echo "    ❌ Required secret ${vault_key} not found in ${VAULT_PATH}"
            return 1
        else
            echo "    ⚠️  Optional secret ${vault_key} not found, skipping"
        fi
    fi
}

# Fetch database secrets
echo "📊 Fetching database configuration..."
fetch_secret "DATABASE_URL" "database_url"

# Fetch application secrets
echo "🔑 Fetching application secrets..."
fetch_secret "APP_SECRET" "app_secret"

# Fetch optional secrets (won't fail if missing)
echo "📧 Fetching optional configuration..."
fetch_secret "MAILER_DSN" "mailer_dsn" false
fetch_secret "REDIS_URL" "redis_url" false

# Fetch custom application secrets
echo "🎯 Fetching custom application secrets..."
fetch_secret "JWT_SECRET_KEY" "jwt_secret_key" false
fetch_secret "JWT_PUBLIC_KEY" "jwt_public_key" false
fetch_secret "JWT_PASSPHRASE" "jwt_passphrase" false

# Add any additional static configuration
cat >> "$ENV_FILE" << EOF

# Static configuration
APP_TIMEZONE=UTC
TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
TRUSTED_HOSTS='^dashboard\.patricklehmann\.dev$'

# Logging
LOG_LEVEL=info
LOG_CHANNEL=stderr

EOF

echo ""
echo "✅ Environment file generated successfully!"
echo "📄 File: ${ENV_FILE}"
echo "📏 Size: $(wc -l < "$ENV_FILE") lines"

# Validate the generated file
echo ""
echo "🧪 Validating environment file..."

# Check for required variables
required_vars=("APP_ENV" "APP_SECRET" "DATABASE_URL")
missing_vars=()

for var in "${required_vars[@]}"; do
    if ! grep -q "^${var}=" "$ENV_FILE"; then
        missing_vars+=("$var")
    fi
done

if [ ${#missing_vars[@]} -gt 0 ]; then
    echo "❌ Missing required variables: ${missing_vars[*]}"
    exit 1
fi

echo "✅ All required variables present"

# Set appropriate permissions
chmod 600 "$ENV_FILE"
echo "🔒 File permissions set to 600 (owner read/write only)"

echo ""
echo "🎉 Vault secrets successfully fetched and environment file created!"
echo ""
echo "📋 Next steps:"
echo "1. Review the generated file: cat ${ENV_FILE}"
echo "2. Test your application with the new environment"
echo "3. Deploy your application"
echo ""
echo "⚠️  Security reminder:"
echo "   - The generated file contains sensitive data"
echo "   - Ensure it's not committed to version control"
echo "   - Use appropriate file permissions in production" 