#!/bin/bash

# Script to troubleshoot HashiCorp Vault access issues
# Usage: ./scripts/vault-troubleshoot.sh

set -e

echo "🔍 HashiCorp Vault Troubleshooting"
echo "=================================="
echo ""

# Check if Vault CLI is available
if ! command -v vault >/dev/null 2>&1; then
    echo "❌ Vault CLI not found. Please install HashiCorp Vault CLI"
    echo "   https://developer.hashicorp.com/vault/downloads"
    exit 1
fi

echo "✅ Vault CLI found: $(vault version)"
echo ""

# Check environment variables
echo "🔧 Environment Configuration:"
echo "   VAULT_ADDR: ${VAULT_ADDR:-'❌ Not set'}"
echo "   VAULT_TOKEN: ${VAULT_TOKEN:+✅ Set (hidden)}"
if [ -z "$VAULT_TOKEN" ]; then
    echo "   VAULT_TOKEN: ❌ Not set"
fi
echo ""

# Check Vault connectivity
echo "🌐 Testing Vault Connectivity..."
if vault status >/dev/null 2>&1; then
    echo "✅ Can connect to Vault server"
    vault status
else
    echo "❌ Cannot connect to Vault server"
    echo "   Please check:"
    echo "   - VAULT_ADDR is correct"
    echo "   - Vault server is running and accessible"
    echo "   - Network connectivity"
    exit 1
fi
echo ""

# Check authentication
echo "🔐 Testing Authentication..."
if vault token lookup >/dev/null 2>&1; then
    echo "✅ Successfully authenticated"
    echo "Token details:"
    vault token lookup | grep -E "(display_name|policies|ttl)"
else
    echo "❌ Authentication failed"
    echo "   Please check your VAULT_TOKEN"
    exit 1
fi
echo ""

# Check KV engine version
echo "🗄️  Checking KV Engine Version..."
if vault secrets list -format=json | jq -r '.["secret/"].options.version' 2>/dev/null; then
    KV_VERSION=$(vault secrets list -format=json | jq -r '.["secret/"].options.version' 2>/dev/null)
    echo "✅ KV Engine Version: $KV_VERSION"
    
    if [ "$KV_VERSION" = "2" ]; then
        echo "   Using KV v2 - correct syntax: vault kv put secret/path key=value"
    else
        echo "   Using KV v1 - syntax: vault write secret/path key=value"
    fi
else
    echo "⚠️  Could not determine KV engine version"
    echo "   Assuming KV v2"
fi
echo ""

# Test specific path access
echo "🎯 Testing Dashboard Path Access..."
DASHBOARD_PATH="secret/dashboard/production"

echo "Testing capabilities for: $DASHBOARD_PATH"
if vault token capabilities "$DASHBOARD_PATH" 2>/dev/null; then
    CAPS=$(vault token capabilities "$DASHBOARD_PATH" 2>/dev/null)
    echo "✅ Path capabilities: $CAPS"
    
    if echo "$CAPS" | grep -q "read"; then
        echo "✅ Read access: Available"
    else
        echo "❌ Read access: Missing"
    fi
    
    if echo "$CAPS" | grep -q "create\|update"; then
        echo "✅ Write access: Available"
    else
        echo "❌ Write access: Missing"
    fi
else
    echo "❌ Cannot check capabilities for $DASHBOARD_PATH"
fi
echo ""

# Test reading from the path
echo "📖 Testing Read Access..."
if vault kv get "$DASHBOARD_PATH" >/dev/null 2>&1; then
    echo "✅ Can read from $DASHBOARD_PATH"
    echo "Available secrets:"
    vault kv get -format=json "$DASHBOARD_PATH" | jq -r '.data.data | keys[]' 2>/dev/null || echo "   (Could not list keys)"
else
    echo "❌ Cannot read from $DASHBOARD_PATH"
    echo "   Error details:"
    vault kv get "$DASHBOARD_PATH" 2>&1 | sed 's/^/   /'
fi
echo ""

# Provide solutions
echo "🛠️  Solutions:"
echo ""

if ! vault token capabilities "$DASHBOARD_PATH" 2>/dev/null | grep -q "read"; then
    echo "❌ Missing Read Permissions:"
    echo "   You need a Vault policy that grants read access to the dashboard secrets."
    echo ""
    echo "   Example policy (save as dashboard-policy.hcl):"
    echo "   ----------------------------------------"
    cat << 'EOF'
   # Dashboard application policy
   path "secret/data/dashboard/*" {
     capabilities = ["read"]
   }
   
   path "secret/metadata/dashboard/*" {
     capabilities = ["read", "list"]
   }
   
   # Temporary write access for initial setup
   path "secret/data/dashboard/*" {
     capabilities = ["create", "update"]
   }
EOF
    echo "   ----------------------------------------"
    echo ""
    echo "   To apply this policy (requires admin access):"
    echo "   vault policy write dashboard-policy dashboard-policy.hcl"
    echo "   vault token create -policy=dashboard-policy"
    echo ""
fi

if ! vault kv get "$DASHBOARD_PATH" >/dev/null 2>&1; then
    echo "📝 Creating Initial Secrets:"
    echo "   Once you have write permissions, create the secrets:"
    echo ""
    echo "   # Required secrets"
    echo "   vault kv put secret/dashboard/production \\"
    echo "     APP_SECRET=\"\$(openssl rand -hex 32)\" \\"
    echo "     DATABASE_URL=\"mysql://username:password@host:3306/dashboard_prod\""
    echo ""
    echo "   # Optional secrets"
    echo "   vault kv put secret/dashboard/production \\"
    echo "     MAILER_DSN=\"smtp://user:pass@smtp.example.com:587\" \\"
    echo "     REDIS_URL=\"redis://localhost:6379\""
    echo ""
fi

echo "🔄 Next Steps:"
echo "1. If you have admin access: Create and apply the policy above"
echo "2. If you don't have admin access: Share this output with your Vault administrator"
echo "3. Once permissions are fixed: Run ./scripts/fetch-vault-secrets.sh to test"
echo "4. Deploy your application with the new secrets"
echo ""

echo "📞 Need Help?"
echo "   - Vault documentation: https://developer.hashicorp.com/vault/docs"
echo "   - Policy documentation: https://developer.hashicorp.com/vault/docs/concepts/policies"
echo "   - KV v2 documentation: https://developer.hashicorp.com/vault/docs/secrets/kv/kv-v2" 