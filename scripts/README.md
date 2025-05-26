# Utility Scripts

This directory contains utility scripts to help manage your deployment workflow.

## Scripts

### `update-vault-version.sh`

Updates the Vault version in your GitHub Actions workflow.

**Usage:**
```bash
./scripts/update-vault-version.sh <version>
```

**Example:**
```bash
# Update to the latest Vault version
./scripts/update-vault-version.sh 1.19.4
```

**What it does:**
- Fetches the SHA256 checksum for the specified Vault version
- Updates the `VAULT_VERSION` and `VAULT_SHA256` environment variables in `.github/workflows/deploy.yml`
- Creates a backup of the workflow file
- Shows you the changes made

### `check-server-vault.sh`

Checks the Vault version and status on your deployment server.

### `test-docker-build.sh`

Tests Docker builds locally to debug issues before pushing to CI/CD.

**Usage:**
```bash
# Set your server details
export SERVER_IP=your.server.ip
export SERVER_PORT=22
export SERVER_USER=patrick

# Run the check
./scripts/check-server-vault.sh
```

**What it checks:**
- Server information (hostname, OS)
- Vault installation and version
- Vault server process status
- Vault accessibility
- Docker installation and status
- Docker Swarm status

**Usage:**
```bash
# Test production build (default)
./scripts/test-docker-build.sh

# Test development build
./scripts/test-docker-build.sh development
```

**What it does:**
- Builds the Docker image locally with verbose output
- Tests the built image by running PHP and Composer
- Shows image details and size
- Provides cleanup instructions

## Quick Start

1. **Check your current server Vault version:**
   ```bash
   export SERVER_IP=your.server.ip
   ./scripts/check-server-vault.sh
   ```

2. **Update to latest Vault version:**
   ```bash
   ./scripts/update-vault-version.sh 1.19.4
   ```

3. **Test Docker build locally (optional but recommended):**
   ```bash
   ./scripts/test-docker-build.sh
   ```

4. **Commit and push the changes:**
   ```bash
   git add .github/workflows/deploy.yml
   git commit -m "Update Vault to version 1.19.4"
   git push
   ```

## Environment Variables in Workflow

The workflow now uses these environment variables for easy version management:

```yaml
env:
  VAULT_VERSION: "1.15.4"
  VAULT_SHA256: "f42f550713e87cceef2f29a4e2b754491697475e3d26c0c5616314e40edd8e1b"
```

This makes it easy to update Vault versions without manually editing multiple places in the workflow file. 