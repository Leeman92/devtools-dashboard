# Deployment Scripts

This directory contains the core deployment scripts used by the GitHub Actions CI/CD pipeline for building and deploying the DevTools Dashboard application.

## Scripts Overview

These scripts are designed to be modular, secure, and reusable. Each script has a single responsibility and implements comprehensive error handling and security practices.

### Installation & Authentication

#### `install-vault.sh`
Installs HashiCorp Vault CLI with checksum verification.

**Usage**: `./install-vault.sh [version] [sha256]`

**Environment Variables**:
- `VAULT_VERSION` (optional, defaults to 1.19.4)
- `VAULT_SHA256` (optional, defaults to corresponding checksum)

#### `setup-vault-auth.sh`
Authenticates with Vault using GitHub OIDC tokens.

**Required Environment Variables**:
- `VAULT_ADDR`
- `ACTIONS_ID_TOKEN_REQUEST_TOKEN`
- `ACTIONS_ID_TOKEN_REQUEST_URL`

**Outputs**: Sets `VAULT_TOKEN` in `$GITHUB_ENV`

### Secret Management

#### `fetch-build-secrets.sh`
Fetches Harbor registry credentials from Vault for Docker builds.

**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Sets `HARBOR_USERNAME` and `HARBOR_PASSWORD` in `$GITHUB_ENV`

#### `fetch-deploy-secrets.sh`
Fetches deployment secrets including server connection details and SSH keys.

**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Sets `SERVER_IP`, `SERVER_PORT`, and `SSH_PRIVATE_KEY` in `$GITHUB_ENV`

#### `generate-env-file.sh`
Generates production environment file from Vault secrets.

**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Creates `.env.production` file with application secrets

### Deployment

#### `deploy-to-server.sh`
Handles the complete server deployment process including Docker Swarm setup, config management, and health checks.

**Context**: Runs on the remote server via SSH

**Features**:
- Docker Swarm initialization
- Network setup
- Versioned configuration management
- Image deployment with health checks
- Automatic cleanup of old configurations

## Security Features

All scripts implement:

- **Secret Masking**: Sensitive values are masked in GitHub Actions logs
- **Error Handling**: Strict error handling with `set -euo pipefail`
- **Input Validation**: Required environment variables are validated
- **Least Privilege**: Scripts request minimum required permissions

## Usage in CI/CD

These scripts are called by the GitHub Actions workflow (`.github/workflows/deploy.yml`):

```yaml
# Build job
- name: Install Vault CLI
  run: ./scripts/deployment/install-vault.sh
- name: Authenticate with Vault
  run: ./scripts/deployment/setup-vault-auth.sh
- name: Fetch build secrets from Vault
  run: ./scripts/deployment/fetch-build-secrets.sh

# Deploy job
- name: Fetch deployment secrets from Vault
  run: ./scripts/deployment/fetch-deploy-secrets.sh
- name: Generate environment file from Vault
  run: ./scripts/deployment/generate-env-file.sh
```

## Local Testing

Scripts can be tested locally (with appropriate Vault access):

```bash
# Make executable
chmod +x *.sh

# Test Vault installation
./install-vault.sh

# Test with Vault credentials
export VAULT_ADDR="https://your-vault-instance"
export VAULT_TOKEN="your-token"
./generate-env-file.sh
```

## Error Handling

All scripts provide:
- Descriptive error messages
- Proper exit codes
- Context for debugging
- No exposure of sensitive information in errors

## Contributing

When modifying these scripts:
1. Maintain backward compatibility
2. Update documentation
3. Test both locally and in CI/CD
4. Follow existing security patterns
5. Ensure proper error handling 