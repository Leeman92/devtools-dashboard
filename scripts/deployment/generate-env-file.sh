#!/bin/bash

# generate-env-file.sh
# Script to generate .env.production file from Vault secrets
# Usage: ./generate-env-file.sh
#
# Features:
# - Fetches all required secrets from HashiCorp Vault
# - Handles multiline secrets properly with quoting
# - Masks secrets in CI/CD environments (GitHub Actions, GitLab CI)
# - Validates all required variables are present
# - Supports optional secrets (MAILER_DSN, REDIS_URL)

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

# Function to safely mask secrets for CI/CD
mask_secret() {
    local secret_value="$1"
    
    # Only mask if we're in a CI/CD environment (GitHub Actions, GitLab CI, etc.)
    if [[ -n "${CI:-}" || -n "${GITHUB_ACTIONS:-}" || -n "${GITLAB_CI:-}" ]]; then
        if [[ "$secret_value" == *$'\n'* ]]; then
            # For multiline secrets, mask each line
            while IFS= read -r line; do
                [[ -n "$line" ]] && echo "::add-mask::$line"
            done <<< "$secret_value"
        else
            # For single-line secrets
            echo "::add-mask::$secret_value"
        fi
    fi
}

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

# Define all required secrets (uppercase as stored in Vault)
REQUIRED_SECRETS=(
    "APP_SECRET"
    "JWT_SECRET_KEY"
    "DATABASE_URL"
    "DOCKER_SOCKET_PATH"
    "GITHUB_TOKEN"
    "GITHUB_API_URL"
    "PROMETHEUS_URL"
    "GRAFANA_URL"
)

# Fetch required secrets first
echo "📋 Fetching required secrets..."
declare -A SECRETS

# Fetch all required secrets
for secret in "${REQUIRED_SECRETS[@]}"; do
    if fetch_vault_secret "$secret" "secret/dashboard/production"; then
        SECRETS["$secret"]="$VAULT_SECRET_VALUE"
        # Mark secret as sensitive for CI/CD systems
        mask_secret "$VAULT_SECRET_VALUE"
        echo "✅ $secret retrieved"
    else
        echo "💥 Failed to fetch $secret - deployment cannot continue"
        exit 1
    fi
done

echo "✅ All required secrets retrieved successfully"

# Create the environment file
cat > .env.production << EOF
# Environment file generated from HashiCorp Vault
# Generated on: $(date -u '+%Y-%m-%d %H:%M:%S UTC')
# Environment: production

# Symfony Environment
APP_ENV=production
APP_DEBUG=true
EOF

# Add each secret with proper handling for multiline values
for secret in "${REQUIRED_SECRETS[@]}"; do
    echo "" >> .env.production
    case "$secret" in
        "APP_SECRET")
            echo "# Application Secrets from Vault" >> .env.production
            ;;
        "JWT_SECRET_KEY")
            echo "# JWT Authentication" >> .env.production
            ;;
        "DATABASE_URL")
            echo "# Database Configuration" >> .env.production
            ;;
        "DOCKER_SOCKET_PATH")
            echo "# Docker Configuration" >> .env.production
            ;;
        "GITHUB_TOKEN")
            echo "# GitHub Integration" >> .env.production
            ;;
        "PROMETHEUS_URL")
            echo "# Infrastructure Monitoring" >> .env.production
            ;;
    esac
    
    # Handle multiline secrets properly
    if [[ "${SECRETS[$secret]}" == *$'\n'* ]]; then
        # For multiline secrets, use proper quoting
        echo "${secret}=\"${SECRETS[$secret]}\"" >> .env.production
    else
        # For single-line secrets
        echo "${secret}=${SECRETS[$secret]}" >> .env.production
    fi
done

# Add optional secrets if they exist
echo "📧 Fetching optional secrets..."
OPTIONAL_SECRETS=("MAILER_DSN" "REDIS_URL")

for secret in "${OPTIONAL_SECRETS[@]}"; do
    if fetch_vault_secret "$secret" "secret/dashboard/production" 2>/dev/null; then
        # Mark secret as sensitive for CI/CD systems
        mask_secret "$VAULT_SECRET_VALUE"
        
        # Handle multiline secrets properly in env file
        if [[ "$VAULT_SECRET_VALUE" == *$'\n'* ]]; then
            # For multiline secrets, use proper quoting in env file
            echo "${secret}=\"${VAULT_SECRET_VALUE}\"" >> .env.production
        else
            # For single-line secrets
            echo "${secret}=${VAULT_SECRET_VALUE}" >> .env.production
        fi
        echo "✅ $secret added"
    else
        echo "⚠️  $secret not found, skipping"
    fi
done

# Add static configuration
cat >> .env.production << EOF

# Static configuration
APP_TIMEZONE=UTC
TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
TRUSTED_HOSTS='^dashboard\.patricklehmann\.dev$'

# Logging Configuration
LOG_LEVEL=info
LOG_LEVEL_DEFAULT=info
LOG_CHANNEL=stderr
MONOLOG_JSON_FORMAT=true

# Force error logging to always be enabled
APP_LOG_LEVEL=error
EOF

echo ""
echo "✅ Environment file generated successfully"
echo "📏 File size: $(wc -l < .env.production) lines"
echo "🔍 Validating required variables..."

# Validate all required variables are present
echo "🔍 Validating all required variables are present..."
VALIDATION_FAILED=false

for secret in "${REQUIRED_SECRETS[@]}"; do
    if ! grep -q "^${secret}=" .env.production; then
        echo "❌ $secret is missing from .env.production"
        VALIDATION_FAILED=true
    else
        echo "✅ $secret is present"
    fi
done

if [ "$VALIDATION_FAILED" = true ]; then
    echo ""
    echo "💥 Validation failed - required variables missing"
    echo "⚠️  Environment file structure (secrets redacted):"
    grep -E "^[A-Z_]+=.*" .env.production | sed 's/=.*/=***REDACTED***/'
    exit 1
fi

echo ""
echo "✅ Validation passed - all required variables present"
echo "📊 Generated environment file contains $(grep -c "^[A-Z_].*=" .env.production) variables"

# Test multiline secret handling
echo ""
echo "🧪 Testing multiline secret handling..."
MULTILINE_TEST="line1
line2
line3"

# Test the mask function
echo "Testing mask function with multiline content..."
if [[ -n "${CI:-}" || -n "${GITHUB_ACTIONS:-}" || -n "${GITLAB_CI:-}" ]]; then
    mask_secret "$MULTILINE_TEST"
    echo "✅ Multiline masking test completed (CI environment detected)"
else
    echo "ℹ️  Multiline masking test skipped (not in CI environment)"
fi

# Verify environment file format
if grep -q '^[A-Z_]*=".*"$' .env.production; then
    echo "✅ Environment file contains properly quoted multiline values"
else
    echo "ℹ️  No multiline values detected in environment file"
fi 