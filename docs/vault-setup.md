# HashiCorp Vault Integration Setup

This guide explains how to set up HashiCorp Vault integration for the dashboard application.

## Overview

The application uses HashiCorp Vault to manage secrets securely. During deployment, secrets are fetched from Vault and used to generate environment files for the Symfony application.

## Architecture

```
GitHub Actions → Vault (fetch secrets) → Generate .env.production → Deploy to Docker Swarm
```

## Setup Steps

### 1. Store Secrets in Vault

First, store your application secrets in Vault using the correct path structure:

```bash
# Required secrets
vault kv put secret/dashboard/production \
  APP_SECRET="$(openssl rand -hex 32)" \
  DATABASE_URL="mysql://username:password@host:3306/dashboard_prod"

# Optional secrets
vault kv put secret/dashboard/production \
  MAILER_DSN="smtp://user:pass@smtp.example.com:587" \
  REDIS_URL="redis://localhost:6379"
```

### 2. Verify Vault Access

Test that your Vault setup is working:

```bash
# Check Vault status
vault status

# List secrets
vault kv list secret/dashboard/

# Get specific secret
vault kv get secret/dashboard/production
```

### 3. Local Development

For local development, you can use the fetch script:

```bash
# Generate .env.local from Vault
./scripts/fetch-vault-secrets.sh development

# Or for staging
./scripts/fetch-vault-secrets.sh staging
```

### 4. Production Deployment

The GitHub Actions workflow automatically:

1. Authenticates with Vault using OIDC
2. Fetches secrets from `secret/dashboard/production`
3. Generates `.env.production` file
4. Deploys it as a Docker config
5. Mounts it into the container as `/app/.env.local`

## File Structure

```
backend/
├── .env.local          # Generated from Vault (local dev)
├── .env.production     # Generated from Vault (production)
└── .env               # Default values (committed)

scripts/
└── fetch-vault-secrets.sh  # Script to fetch secrets locally

docs/
├── vault-secrets-template.md  # What secrets to store
└── vault-setup.md            # This file
```

## Environment File Priority

Symfony loads environment files in this order (later files override earlier ones):

1. `.env` (committed, default values)
2. `.env.local` (generated from Vault, not committed)
3. `.env.$APP_ENV` (e.g., `.env.production`)
4. `.env.$APP_ENV.local`

## Security Considerations

### ✅ Best Practices

- **Secrets in Vault**: All sensitive data stored in Vault
- **Environment Separation**: Different secrets for each environment
- **File Permissions**: Environment files have 600 permissions
- **No Commits**: Generated files are not committed to git
- **Audit Trail**: Vault provides access logging

### ⚠️ Important Notes

- The `.env.production` file contains sensitive data
- It's generated during deployment and mounted into containers
- Local development uses `.env.local` (also not committed)
- Never commit files containing real secrets

## Troubleshooting

### Vault Connection Issues

```bash
# Check Vault configuration
echo "VAULT_ADDR: $VAULT_ADDR"
echo "VAULT_TOKEN: ${VAULT_TOKEN:+Set}"

# Test connection
vault status
```

### Missing Secrets

```bash
# List all secrets in path
vault kv list secret/dashboard/

# Check specific environment
vault kv get secret/dashboard/production
```

### GitHub Actions Failures

1. **Vault Authentication**: Check OIDC configuration
2. **Missing Secrets**: Ensure all required secrets exist
3. **Permissions**: Verify Vault policies allow access

### Local Development Issues

```bash
# Generate environment file manually
./scripts/fetch-vault-secrets.sh development

# Check generated file
cat backend/.env.local

# Test Symfony with new environment
cd backend && php bin/console about
```

## Vault Policies

Example Vault policy for the application:

```hcl
# Policy for dashboard application
path "secret/data/dashboard/*" {
  capabilities = ["read"]
}

path "secret/metadata/dashboard/*" {
  capabilities = ["read"]
}
```

## Monitoring

### Health Checks

The application should include health checks that verify:

- Database connectivity
- Required environment variables are set
- External services are accessible

### Logging

Environment file generation is logged in:

- GitHub Actions output
- Local script output
- Docker deployment logs

## Migration from .env Files

If migrating from existing `.env` files:

1. **Audit Current Secrets**: List all environment variables
2. **Store in Vault**: Use the template as a guide
3. **Test Locally**: Generate and test new environment files
4. **Deploy Gradually**: Test in staging before production
5. **Clean Up**: Remove old `.env` files from servers

## Commands Reference

```bash
# Local development
./scripts/fetch-vault-secrets.sh development

# View generated file
cat backend/.env.local

# Test Symfony configuration
cd backend && php bin/console debug:config

# Deploy nginx configuration
sudo ./scripts/deploy-nginx-config.sh

# Check Docker configs
docker config ls

# View container environment
docker exec -it <container> env | grep APP_
``` 