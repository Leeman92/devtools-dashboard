#!/bin/bash

# setup-standalone-mysql.sh
# Script to set up a standalone MySQL container for DevTools Dashboard
# Passwords are automatically generated and stored in HashiCorp Vault
# Usage: ./setup-standalone-mysql.sh [environment]

set -euo pipefail

ENVIRONMENT="${1:-production}"
VAULT_PATH="secret/dashboard/${ENVIRONMENT}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color



print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}


docker ps -a --filter name=dashboard-mysql
docker stop dashboard-mysql || true
docker rm dashboard-mysql || true
docker stop mysql-test || true
docker rm mysql-test || true


print_status $BLUE "\U0001f5c8️  Setting up standalone MySQL container for DevTools Dashboard"
echo "\xf0\x9f\x93\x8d Environment: ${ENVIRONMENT}"
echo "\xf0\x9f\x93\x82 Vault path: ${VAULT_PATH}"
echo ""

# Configuration
MYSQL_CONTAINER_NAME="dashboard-mysql"
MYSQL_VERSION="10.11"
VOLUME_NAME="dashboard-mysql-data"
NETWORK_NAME="dashboard-network"

# Check required environment variables
if [[ -z "${VAULT_ADDR:-}" ]]; then
    print_status $RED "❌ VAULT_ADDR environment variable is required"
    exit 1
fi

if [[ -z "${VAULT_TOKEN:-}" ]]; then
    print_status $RED "❌ VAULT_TOKEN environment variable is required"
    exit 1
fi

# Test Vault connectivity
if ! vault status >/dev/null 2>&1; then
    print_status $RED "❌ Cannot connect to Vault at $VAULT_ADDR"
    exit 1
fi

print_status $GREEN "✅ Vault connected"

# Reset Docker volume for clean initialization
print_status $YELLOW "📦 Resetting MySQL volume for clean initialization..."
docker volume rm "$VOLUME_NAME" >/dev/null 2>&1 || true
docker volume create "$VOLUME_NAME"
print_status $GREEN "✅ Fresh volume created: $VOLUME_NAME"

# Generate passwords
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-32
}

ROOT_PASSWORD=$(generate_password)
DASHBOARD_PASSWORD=$(generate_password)
DATABASE_URL="mysql://dashboard:${DASHBOARD_PASSWORD}@${MYSQL_CONTAINER_NAME}:3306/dashboard?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

print_status $YELLOW "🔐 Generated MySQL credentials"

# Stop and remove container if exists
if docker ps -a --format '{{.Names}}' | grep -q "^$MYSQL_CONTAINER_NAME$"; then
    docker stop "$MYSQL_CONTAINER_NAME" >/dev/null || true
    docker rm "$MYSQL_CONTAINER_NAME" >/dev/null || true
    print_status $YELLOW "🗑️  Removed existing container"
fi

# Create network if needed
print_status $YELLOW "🌐 Checking Docker network..."
if ! docker network inspect "$NETWORK_NAME" >/dev/null 2>&1; then
    print_status $YELLOW "📡 Network '$NETWORK_NAME' not found, creating..."
    docker network create "$NETWORK_NAME"
    print_status $GREEN "✅ Network created: $NETWORK_NAME"
else
    print_status $BLUE "📡 Network '$NETWORK_NAME' already exists"
fi

# Run MySQL container
print_status $YELLOW "🚀 Starting MySQL container..."
docker run -d \
    --name "$MYSQL_CONTAINER_NAME" \
    --network "$NETWORK_NAME" \
    --restart unless-stopped \
    -v "$VOLUME_NAME:/var/lib/mysql" \
    -e MYSQL_ROOT_PASSWORD="$ROOT_PASSWORD" \
    mariadb:$MYSQL_VERSION \
    --character-set-server=utf8mb4 \
    --collation-server=utf8mb4_unicode_ci \
    --innodb-buffer-pool-size=256M \
    --max-connections=100 \
    --query-cache-size=32M

# Wait for MySQL to be ready
print_status $YELLOW "⏳ Waiting up to 60s for MariaDB to become ready..."
attempts=30
while ! docker exec "$MYSQL_CONTAINER_NAME" mysqladmin ping -p"$ROOT_PASSWORD" --silent >/dev/null 2>&1 && [ $attempts -gt 0 ]; do
    sleep 2
    attempts=$((attempts-1))
done

if [ $attempts -eq 0 ]; then
    print_status $RED "❌ MySQL did not become ready"
    docker logs "$MYSQL_CONTAINER_NAME"
    exit 1
fi

print_status $GREEN "✅ MySQL is up and authenticated with root password"

# Force fix root plugin and create DB/user
print_status $YELLOW "🔐 Forcing root plugin fix and setting up DB/user..."
docker exec "$MYSQL_CONTAINER_NAME" bash -s <<EOF
mysql -u root --protocol=socket <<EOSQL
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$ROOT_PASSWORD';
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '$ROOT_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
CREATE DATABASE IF NOT EXISTS dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'dashboard'@'%' IDENTIFIED BY '$DASHBOARD_PASSWORD';
GRANT ALL PRIVILEGES ON dashboard.* TO 'dashboard'@'%';
FLUSH PRIVILEGES;
EOSQL
EOF

# Store secrets in Vault
print_status $YELLOW "💾 Storing secrets in Vault..."
EXISTING_SECRETS=""
if vault kv get "$VAULT_PATH" >/dev/null 2>&1; then
    EXISTING_SECRETS=$(vault kv get -format=json "$VAULT_PATH" | jq -r '.data.data | to_entries[] | select(.key != "DATABASE_URL" and .key != "MYSQL_ROOT_PASSWORD" and .key != "MYSQL_DASHBOARD_PASSWORD") | "\(.key)=\"\(.value)\""' | tr '\n' ' ')
fi

vault kv put $VAULT_PATH $EXISTING_SECRETS \
    DATABASE_URL="$DATABASE_URL" \
    MYSQL_ROOT_PASSWORD="$ROOT_PASSWORD" \
    MYSQL_DASHBOARD_PASSWORD="$DASHBOARD_PASSWORD"

print_status $GREEN "✅ All done! MySQL is configured and credentials are in Vault."
echo ""
print_status $BLUE "🔧 Connect with:"
echo "   docker exec -it $MYSQL_CONTAINER_NAME mysql -uroot -p"
echo "   docker exec -it $MYSQL_CONTAINER_NAME mysql -udashboard -p"
