#!/bin/bash

# DevTools Dashboard - Setup Validation Script
# Run this script before building Docker images or deploying
# Uses Docker containers - no local PHP/Composer required

set -e

echo "🔍 DevTools Dashboard - Setup Validation"
echo "========================================"

# Change to backend directory
cd "$(dirname "$0")/../backend"

echo "📁 Current directory: $(pwd)"

# Check if required files exist
echo "📋 Checking required files..."
required_files=("composer.json" "composer.lock" "symfony.lock" ".env.example")
for file in "${required_files[@]}"; do
    if [[ -f "$file" ]]; then
        echo "✅ $file exists"
    else
        echo "❌ $file is missing"
        exit 1
    fi
done

# Validate composer files using Docker
echo "🎼 Validating Composer configuration..."
if docker run --rm -v "$(pwd):/app" -w /app composer:latest validate --strict; then
    echo "✅ Composer configuration is valid"
else
    echo "❌ Composer configuration has issues"
    exit 1
fi

# Check if composer.lock is up to date using Docker
echo "🔄 Checking if composer.lock is up to date..."
if docker run --rm -v "$(pwd):/app" -w /app composer:latest check-platform-reqs --lock; then
    echo "✅ Composer lock file is compatible"
else
    echo "⚠️  Composer lock file may have platform requirement issues"
fi

# Check for common environment variables
echo "🌍 Checking environment configuration..."
if [[ -f ".env" ]]; then
    echo "✅ .env file exists"
    
    # Check for required environment variables
    required_vars=("DATABASE_URL" "DOCKER_SOCKET_PATH" "GITHUB_TOKEN")
    for var in "${required_vars[@]}"; do
        if grep -q "^${var}=" .env; then
            echo "✅ $var is configured"
        else
            echo "⚠️  $var is not configured in .env"
        fi
    done
else
    echo "⚠️  .env file not found - copy from .env.example"
fi

# Check Docker socket access (if running on host with Docker)
echo "🐳 Checking Docker access..."
if [[ -S "/var/run/docker.sock" ]]; then
    echo "✅ Docker socket is accessible"
else
    echo "⚠️  Docker socket not found (normal in containers)"
fi

# Check PHP version compatibility using Docker
echo "🐘 Checking PHP compatibility..."
php_version=$(docker run --rm php:8.4-cli php -r "echo PHP_VERSION;")
echo "📌 Docker PHP version: $php_version"

if docker run --rm php:8.4-cli php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
    echo "✅ PHP version is compatible (8.2+)"
else
    echo "❌ PHP version must be 8.2 or higher"
    exit 1
fi

# Test basic Symfony console using Docker
echo "🎯 Testing Symfony console..."
if docker run --rm -v "$(pwd):/app" -w /app php:8.4-cli php bin/console --version > /dev/null 2>&1; then
    echo "✅ Symfony console is working"
else
    echo "❌ Symfony console has issues - try running 'composer install' first"
fi

echo ""
echo "🎉 Setup validation completed successfully!"
echo ""
echo "📝 Next steps:"
echo "   1. Install dependencies: 'docker run --rm -v \$(pwd):/app -w /app composer:latest install'"
echo "   2. Configure your .env file with actual values"
echo "   3. Run database migrations: 'docker run --rm -v \$(pwd):/app -w /app php:8.4-cli php bin/console doctrine:migrations:migrate'"
echo "   4. Test Docker build: 'docker build -f .docker/Dockerfile .'"
echo "" 