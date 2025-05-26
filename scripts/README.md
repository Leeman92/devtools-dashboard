# DevTools Dashboard - Scripts

This directory contains bash scripts used by the GitHub Actions CI/CD pipeline and for local development and maintenance tasks.

## Directory Structure

```
scripts/
├── deployment/          # Core deployment scripts used by CI/CD
├── README.md           # This documentation
└── [utility scripts]   # Various utility and maintenance scripts
```

## Deployment Scripts (`deployment/`)

The core deployment process has been broken down into smaller, reusable scripts located in the `deployment/` subdirectory. Each script has a single responsibility and can be run independently or as part of the CI/CD pipeline.

### Core Deployment Scripts

#### `deployment/install-vault.sh`
**Purpose**: Install HashiCorp Vault CLI  
**Usage**: `./deployment/install-vault.sh [version] [sha256]`  
**Environment Variables**: 
- `VAULT_VERSION` (optional, defaults to 1.19.4)
- `VAULT_SHA256` (optional, defaults to corresponding checksum)

Downloads, verifies, and installs the Vault CLI binary.

#### `deployment/setup-vault-auth.sh`
**Purpose**: Authenticate with Vault using GitHub OIDC  
**Usage**: `./deployment/setup-vault-auth.sh`  
**Required Environment Variables**:
- `VAULT_ADDR`
- `ACTIONS_ID_TOKEN_REQUEST_TOKEN`
- `ACTIONS_ID_TOKEN_REQUEST_URL`

**Outputs**: Sets `VAULT_TOKEN` in `$GITHUB_ENV`

#### `deployment/fetch-build-secrets.sh`
**Purpose**: Fetch Harbor registry credentials from Vault  
**Usage**: `./deployment/fetch-build-secrets.sh`  
**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Sets `HARBOR_USERNAME` and `HARBOR_PASSWORD` in `$GITHUB_ENV`

#### `deployment/fetch-deploy-secrets.sh`
**Purpose**: Fetch deployment secrets (server connection details and SSH key)  
**Usage**: `./deployment/fetch-deploy-secrets.sh`  
**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Sets `SERVER_IP`, `SERVER_PORT`, and `SSH_PRIVATE_KEY` in `$GITHUB_ENV`

#### `deployment/generate-env-file.sh`
**Purpose**: Generate production environment file from Vault secrets  
**Usage**: `./deployment/generate-env-file.sh`  
**Required Environment Variables**:
- `VAULT_ADDR`
- `VAULT_TOKEN`

**Outputs**: Creates `.env.production` file with application secrets

#### `deployment/deploy-to-server.sh`
**Purpose**: Deploy the application on the target server  
**Usage**: `./deployment/deploy-to-server.sh`  
**Context**: Runs on the remote server via SSH

Handles Docker Swarm setup, config management, image deployment, and health checks.

### Utility Scripts

#### `fetch-vault-secrets.sh`
**Purpose**: General-purpose Vault secret fetching utility  
**Usage**: Various debugging and maintenance tasks

#### `vault-troubleshoot.sh`
**Purpose**: Troubleshoot Vault connectivity and authentication issues  
**Usage**: Debugging Vault-related problems

#### `update-vault-version.sh`
**Purpose**: Update Vault CLI version across the project  
**Usage**: Maintenance and version updates

#### `test-docker-build.sh`
**Purpose**: Test Docker build process locally  
**Usage**: Local development and testing

#### `check-server-vault.sh`
**Purpose**: Check Vault installation and connectivity on the server  
**Usage**: Server diagnostics

#### `deploy-nginx-config.sh`
**Purpose**: Deploy Nginx configuration  
**Usage**: Infrastructure setup

## Security Features

All scripts implement the following security practices:

- **Secret Masking**: All sensitive values are masked in GitHub Actions logs using `::add-mask::`
- **Error Handling**: Scripts use `set -euo pipefail` for strict error handling
- **Input Validation**: Required environment variables are validated before use
- **Least Privilege**: Scripts only request the minimum required permissions

## Usage in GitHub Actions

The main workflow (`.github/workflows/deploy.yml`) has been refactored to use these scripts:

```yaml
# Example usage
- name: Install Vault CLI
  run: ./scripts/deployment/install-vault.sh
  env:
    VAULT_VERSION: ${{ env.VAULT_VERSION }}
    VAULT_SHA256: ${{ env.VAULT_SHA256 }}

- name: Authenticate with Vault
  run: ./scripts/deployment/setup-vault-auth.sh
  env:
    VAULT_ADDR: ${{ secrets.VAULT_ADDR }}
```

## Benefits of Modular Approach

1. **Maintainability**: Each script has a single responsibility
2. **Reusability**: Scripts can be used in different contexts
3. **Testability**: Individual scripts can be tested independently
4. **Readability**: Workflow files are cleaner and easier to understand
5. **Debugging**: Issues can be isolated to specific scripts
6. **Version Control**: Changes to deployment logic are tracked in git

## Local Development

Scripts can be run locally for testing and development:

```bash
# Make scripts executable
chmod +x scripts/deployment/*.sh

# Test Vault installation
./scripts/deployment/install-vault.sh

# Test environment file generation (requires Vault access)
export VAULT_ADDR="https://your-vault-instance"
export VAULT_TOKEN="your-token"
./scripts/deployment/generate-env-file.sh
```

## Error Handling

All scripts implement comprehensive error handling:

- Exit codes are properly propagated
- Error messages are descriptive and actionable
- Sensitive information is never exposed in error messages
- Failed operations are logged with context

## Contributing

When adding new scripts:

1. Follow the existing naming convention
2. Include comprehensive error handling
3. Document required environment variables
4. Add security considerations (masking, validation)
5. Update this README with script documentation
6. Make scripts executable (`chmod +x`)
7. Test scripts both locally and in CI/CD pipeline 