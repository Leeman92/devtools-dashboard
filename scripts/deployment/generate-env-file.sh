#!/bin/bash

# generate-env-file.sh
# Script to generate .env.production file from Vault secrets
# Usage: ./generate-env-file.sh

set -euo pipefail

echo "🔐 Generating .env.production from Vault secrets..."

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

# Fetch required secrets first
echo "📋 Fetching required secrets..."
if fetch_vault_secret "app_secret" "secret/dashboard/production"; then
    APP_SECRET="$VAULT_SECRET_VALUE"
    echo "::add-mask::$APP_SECRET"
else
    echo "💥 Failed to fetch APP_SECRET - deployment cannot continue"
    exit 1
fi

if fetch_vault_secret "database_url" "secret/dashboard/production"; then
    DATABASE_URL="$VAULT_SECRET_VALUE"
    echo "::add-mask::$DATABASE_URL"
else
    echo "💥 Failed to fetch DATABASE_URL - deployment cannot continue"
    exit 1
fi

echo "✅ Required secrets retrieved successfully"

# Create the environment file
cat > .env.production << EOF
# Environment file generated from HashiCorp Vault
# Generated on: $(date -u '+%Y-%m-%d %H:%M:%S UTC')
# Environment: production

# Symfony Environment
APP_ENV=production
APP_DEBUG=false

# Secrets from Vault
APP_SECRET=${APP_SECRET}
DATABASE_URL=${DATABASE_URL}
EOF

# Add optional secrets if they exist
echo "📧 Fetching optional secrets..."

if fetch_vault_secret "mailer_dsn" "secret/dashboard/production" 2>/dev/null; then
    MAILER_DSN="$VAULT_SECRET_VALUE"
    echo "::add-mask::$MAILER_DSN"
    echo "MAILER_DSN=${MAILER_DSN}" >> .env.production
    echo "✅ MAILER_DSN added"
else
    echo "⚠️  MAILER_DSN not found, skipping"
fi

if fetch_vault_secret "redis_url" "secret/dashboard/production" 2>/dev/null; then
    REDIS_URL="$VAULT_SECRET_VALUE"
    echo "::add-mask::$REDIS_URL"
    echo "REDIS_URL=${REDIS_URL}" >> .env.production
    echo "✅ REDIS_URL added"
else
    echo "⚠️  REDIS_URL not found, skipping"
fi

# Add static configuration
cat >> .env.production << EOF

# Static configuration
APP_TIMEZONE=UTC
TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
TRUSTED_HOSTS='^dashboard\.patricklehmann\.dev$'

# Logging
LOG_LEVEL=info
LOG_CHANNEL=stderr
EOF

echo ""
echo "✅ Environment file generated successfully"
echo "📏 File size: $(wc -l < .env.production) lines"
echo "🔍 Validating required variables..."

# Validate required variables are present
if ! grep -q "^APP_SECRET=" .env.production || ! grep -q "^DATABASE_URL=" .env.production; then
    echo "💥 Validation failed - required variables missing"
    echo "🔍 Checking which variables are missing:"
    if ! grep -q "^APP_SECRET=" .env.production; then
        echo "❌ APP_SECRET is missing"
    fi
    if ! grep -q "^DATABASE_URL=" .env.production; then
        echo "❌ DATABASE_URL is missing"
    fi
    echo "⚠️  Environment file structure (secrets redacted):"
    grep -E "^[A-Z_]+=.*" .env.production | sed 's/=.*/=***REDACTED***/'
    exit 1
fi

echo "✅ Validation passed - all required variables present" 