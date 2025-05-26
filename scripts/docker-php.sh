#!/bin/bash

# DevTools Dashboard - Docker PHP Wrapper Script
# Simplifies running PHP/Composer commands through Docker

set -e

# Default to backend directory
WORK_DIR="$(dirname "$0")/../backend"
cd "$WORK_DIR"

# Function to show usage
show_usage() {
    echo "🐳 DevTools Dashboard - Docker PHP Wrapper"
    echo "=========================================="
    echo ""
    echo "Usage: $0 <command> [arguments...]"
    echo ""
    echo "Commands:"
    echo "  composer <args>     - Run composer commands"
    echo "  php <args>          - Run PHP commands"
    echo "  console <args>      - Run Symfony console commands"
    echo "  test [args]         - Run PHPUnit tests"
    echo "  validate            - Validate composer files"
    echo "  install             - Install composer dependencies"
    echo "  update              - Update composer dependencies"
    echo "  migrate             - Run database migrations"
    echo "  create-db           - Create database"
    echo "  collect-metrics     - Collect metrics (with --dry-run by default)"
    echo ""
    echo "Examples:"
    echo "  $0 validate"
    echo "  $0 install"
    echo "  $0 console doctrine:migrations:status"
    echo "  $0 collect-metrics --source=docker"
    echo "  $0 composer require symfony/cache"
    echo ""
}

# Check if command is provided
if [ $# -eq 0 ]; then
    show_usage
    exit 1
fi

COMMAND="$1"
shift

case "$COMMAND" in
    "composer")
        echo "🎼 Running Composer: $*"
        docker run --rm -v "$(pwd):/app" -w /app composer:latest "$@"
        ;;
    
    "php")
        echo "🐘 Running PHP: $*"
        docker run --rm -v "$(pwd):/app" -w /app php:8.4-cli php "$@"
        ;;
    
    "console")
        echo "🎯 Running Symfony Console: $*"
        docker run --rm -v "$(pwd):/app" -w /app --network host php:8.4-cli php bin/console "$@"
        ;;
    
    "test")
        echo "🧪 Running Tests: $*"
        docker run --rm -v "$(pwd):/app" -w /app php:8.4-cli php bin/phpunit "$@"
        ;;
    
    "validate")
        echo "✅ Validating Composer files..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest validate --strict
        ;;
    
    "install")
        echo "📦 Installing dependencies..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest install
        ;;
    
    "update")
        echo "🔄 Updating dependencies..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest update
        echo "✅ Validating after update..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest validate
        ;;
    
    "migrate")
        echo "🗄️  Running database migrations..."
        docker run --rm -v "$(pwd):/app" -w /app --network host php:8.4-cli php bin/console doctrine:migrations:migrate --no-interaction
        ;;
    
    "create-db")
        echo "🗄️  Creating database..."
        docker run --rm -v "$(pwd):/app" -w /app --network host php:8.4-cli php bin/console doctrine:database:create --if-not-exists
        ;;
    
    "collect-metrics")
        if [ $# -eq 0 ]; then
            echo "📊 Collecting metrics (dry-run)..."
            docker run --rm -v "$(pwd):/app" -w /app php:8.4-cli php bin/console app:collect-metrics --dry-run
        else
            echo "📊 Collecting metrics: $*"
            docker run --rm -v "$(pwd):/app" -w /app --network host php:8.4-cli php bin/console app:collect-metrics "$@"
        fi
        ;;
    
    "help"|"--help"|"-h")
        show_usage
        ;;
    
    *)
        echo "❌ Unknown command: $COMMAND"
        echo ""
        show_usage
        exit 1
        ;;
esac 