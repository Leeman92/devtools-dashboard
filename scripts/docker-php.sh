#!/bin/bash

# DevTools Dashboard - Docker PHP Wrapper Script
# Simplifies running PHP/Composer commands through Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Backend container name
BACKEND_CONTAINER="devtools-dashboard-backend-1"

# Default to backend directory for composer operations
WORK_DIR="$(dirname "$0")/../backend"
cd "$WORK_DIR"

# Function to check if backend container is running
check_container() {
    if ! docker ps --format "{{.Names}}" | grep -q "^${BACKEND_CONTAINER}$"; then
        print_status $RED "❌ Backend container '${BACKEND_CONTAINER}' is not running!"
        print_status $YELLOW "💡 Please start the development environment first:"
        echo "   ./scripts/dev.sh"
        echo "   OR"
        echo "   docker compose up -d"
        exit 1
    fi
}

# Function to show usage
show_usage() {
    echo "🐳 DevTools Dashboard - Docker PHP Wrapper"
    echo "=========================================="
    echo ""
    echo "Usage: $0 <command> [arguments...]"
    echo ""
    echo "Commands:"
    echo "  composer <args>     - Run composer commands (uses new container)"
    echo "  php <args>          - Run PHP commands in backend container"
    echo "  console <args>      - Run Symfony console commands in backend container"
    echo "  test [args]         - Run PHPUnit tests in backend container"
    echo "  validate            - Validate composer files (uses new container)"
    echo "  install             - Install composer dependencies (uses new container)"
    echo "  update              - Update composer dependencies (uses new container)"
    echo "  migrate             - Run database migrations in backend container"
    echo "  create-db           - Create database in backend container"
    echo "  generate-metrics    - Generate sample metrics data in backend container"
    echo "  collect-metrics     - Collect real-time metrics from running containers"
    echo ""
    echo "Examples:"
    echo "  $0 validate"
    echo "  $0 install"
    echo "  $0 console doctrine:migrations:status"
    echo "  $0 generate-metrics"
    echo "  $0 collect-metrics --dry-run"
    echo "  $0 composer require symfony/cache"
    echo ""
    echo "Note: Commands that need database access use the running backend container."
    echo "      Composer operations use new containers for isolation."
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
        print_status $BLUE "🎼 Running Composer: $*"
        docker run --rm -v "$(pwd):/app" -w /app composer:latest "$@"
        ;;
    
    "php")
        check_container
        print_status $BLUE "🐘 Running PHP: $*"
        docker exec -it "$BACKEND_CONTAINER" php "$@"
        ;;
    
    "console")
        check_container
        print_status $BLUE "🎯 Running Symfony Console: $*"
        docker exec -it "$BACKEND_CONTAINER" php bin/console "$@"
        ;;
    
    "test")
        check_container
        print_status $BLUE "🧪 Running Tests: $*"
        docker exec -it "$BACKEND_CONTAINER" php bin/phpunit "$@"
        ;;
    
    "validate")
        print_status $BLUE "✅ Validating Composer files..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest validate --strict
        ;;
    
    "install")
        print_status $BLUE "📦 Installing dependencies..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest install
        ;;
    
    "update")
        print_status $BLUE "🔄 Updating dependencies..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest update
        print_status $BLUE "✅ Validating after update..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest validate
        ;;
    
    "migrate")
        check_container
        print_status $BLUE "🗄️  Running database migrations..."
        docker exec -it "$BACKEND_CONTAINER" php bin/console doctrine:migrations:migrate --no-interaction
        ;;
    
    "create-db")
        check_container
        print_status $BLUE "🗄️  Creating database..."
        docker exec -it "$BACKEND_CONTAINER" php bin/console doctrine:database:create --if-not-exists
        ;;
    
    "generate-metrics")
        check_container
        print_status $BLUE "📊 Generating sample metrics data..."
        docker exec -it "$BACKEND_CONTAINER" php bin/console app:generate-metrics "$@"
        ;;
    
    "collect-metrics")
        check_container
        print_status $BLUE "📊 Collecting real-time metrics..."
        docker exec -it "$BACKEND_CONTAINER" php bin/console app:collect-metrics "$@"
        ;;
    
    "help"|"--help"|"-h")
        show_usage
        ;;
    
    *)
        print_status $RED "❌ Unknown command: $COMMAND"
        echo ""
        show_usage
        exit 1
        ;;
esac 