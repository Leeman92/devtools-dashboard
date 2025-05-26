#!/bin/bash

# DevTools Dashboard - Docker Node.js Wrapper
# Provides containerized Node.js/npm operations without requiring local installation

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

# Configuration
NODE_VERSION="20-alpine"
CONTAINER_NAME="devtools-node-temp"
FRONTEND_DIR="frontend"
WORK_DIR="/app"

# Ensure we're in the project root
if [ ! -f "docker-compose.yml" ]; then
    print_status $RED "❌ Error: Must be run from project root (where docker-compose.yml exists)"
    exit 1
fi

# Ensure frontend directory exists
if [ ! -d "$FRONTEND_DIR" ]; then
    print_status $YELLOW "📁 Creating frontend directory..."
    mkdir -p "$FRONTEND_DIR"
fi

# Function to run Node.js commands in Docker
run_node_command() {
    local cmd="$1"
    shift
    local args="$@"
    
    print_status $BLUE "🐳 Running: $cmd $args"
    
    docker run --rm -it \
        --name "$CONTAINER_NAME" \
        -v "$(pwd)/$FRONTEND_DIR:$WORK_DIR" \
        -w "$WORK_DIR" \
        -u "$(id -u):$(id -g)" \
        node:$NODE_VERSION \
        $cmd $args
}

# Function to run npm with proper user permissions
run_npm() {
    run_node_command npm "$@"
}

# Function to run npx
run_npx() {
    run_node_command npx "$@"
}

# Function to run node
run_node() {
    run_node_command node "$@"
}

# Main command handling
case "${1:-help}" in
    "init")
        print_status $YELLOW "🚀 Initializing React TypeScript project with Vite..."
        
        # Create Vite project in a temp directory, then move contents
        docker run --rm -it \
            --name "$CONTAINER_NAME" \
            -v "$(pwd):$WORK_DIR" \
            -w "$WORK_DIR" \
            -u "$(id -u):$(id -g)" \
            node:$NODE_VERSION \
            npx create-vite@latest frontend-temp --template react-ts --yes
        
        # Move contents from temp directory to frontend
        if [ -d "frontend-temp" ]; then
            mv frontend-temp/* frontend/ 2>/dev/null || true
            mv frontend-temp/.* frontend/ 2>/dev/null || true
            rmdir frontend-temp
            print_status $GREEN "✅ React TypeScript project created successfully"
        else
            print_status $RED "❌ Failed to create Vite project"
            exit 1
        fi
        ;;
    
    "install"|"i")
        print_status $YELLOW "📦 Installing dependencies..."
        run_npm install
        print_status $GREEN "✅ Dependencies installed"
        ;;
    
    "add")
        if [ -z "$2" ]; then
            print_status $RED "❌ Error: Please specify package(s) to add"
            echo "Usage: $0 add <package1> [package2] ..."
            exit 1
        fi
        shift
        print_status $YELLOW "📦 Adding packages: $@"
        run_npm install "$@"
        print_status $GREEN "✅ Packages added"
        ;;
    
    "add-dev")
        if [ -z "$2" ]; then
            print_status $RED "❌ Error: Please specify package(s) to add"
            echo "Usage: $0 add-dev <package1> [package2] ..."
            exit 1
        fi
        shift
        print_status $YELLOW "📦 Adding dev packages: $@"
        run_npm install --save-dev "$@"
        print_status $GREEN "✅ Dev packages added"
        ;;
    
    "remove"|"rm")
        if [ -z "$2" ]; then
            print_status $RED "❌ Error: Please specify package(s) to remove"
            echo "Usage: $0 remove <package1> [package2] ..."
            exit 1
        fi
        shift
        print_status $YELLOW "🗑️  Removing packages: $@"
        run_npm uninstall "$@"
        print_status $GREEN "✅ Packages removed"
        ;;
    
    "dev"|"start")
        print_status $YELLOW "🚀 Starting development server..."
        docker run --rm -it \
            --name "devtools-frontend-dev" \
            -v "$(pwd)/$FRONTEND_DIR:$WORK_DIR" \
            -w "$WORK_DIR" \
            -p "5173:5173" \
            -u "$(id -u):$(id -g)" \
            node:$NODE_VERSION \
            npm run dev -- --host 0.0.0.0
        ;;
    
    "build")
        print_status $YELLOW "🏗️  Building for production..."
        run_npm run build
        print_status $GREEN "✅ Build completed"
        ;;
    
    "test")
        print_status $YELLOW "🧪 Running tests..."
        run_npm test
        ;;
    
    "lint")
        print_status $YELLOW "🔍 Running linter..."
        run_npm run lint
        ;;
    
    "format")
        print_status $YELLOW "💅 Formatting code..."
        run_npx prettier --write .
        print_status $GREEN "✅ Code formatted"
        ;;
    
    "npm")
        shift
        run_npm "$@"
        ;;
    
    "npx")
        shift
        run_npx "$@"
        ;;
    
    "node")
        shift
        run_node "$@"
        ;;
    
    "shell"|"bash")
        print_status $YELLOW "🐚 Opening Node.js container shell..."
        docker run --rm -it \
            --name "$CONTAINER_NAME" \
            -v "$(pwd)/$FRONTEND_DIR:$WORK_DIR" \
            -w "$WORK_DIR" \
            -u "$(id -u):$(id -g)" \
            node:$NODE_VERSION \
            sh
        ;;
    
    "clean")
        print_status $YELLOW "🧹 Cleaning node_modules and build artifacts..."
        if [ -d "$FRONTEND_DIR/node_modules" ]; then
            rm -rf "$FRONTEND_DIR/node_modules"
            print_status $GREEN "✅ node_modules removed"
        fi
        if [ -d "$FRONTEND_DIR/dist" ]; then
            rm -rf "$FRONTEND_DIR/dist"
            print_status $GREEN "✅ dist directory removed"
        fi
        ;;
    
    "validate")
        print_status $YELLOW "🔍 Validating frontend setup..."
        
        # Check if package.json exists
        if [ ! -f "$FRONTEND_DIR/package.json" ]; then
            print_status $RED "❌ package.json not found. Run: $0 init"
            exit 1
        fi
        
        # Check if node_modules exists
        if [ ! -d "$FRONTEND_DIR/node_modules" ]; then
            print_status $YELLOW "⚠️  node_modules not found. Run: $0 install"
            exit 1
        fi
        
        print_status $GREEN "✅ Frontend setup is valid"
        ;;
    
    "help"|*)
        echo "DevTools Dashboard - Docker Node.js Wrapper"
        echo ""
        echo "Usage: $0 <command> [arguments]"
        echo ""
        echo "Project Setup:"
        echo "  init                 Initialize new React TypeScript project with Vite"
        echo "  install, i           Install dependencies from package.json"
        echo "  validate             Validate frontend setup"
        echo ""
        echo "Package Management:"
        echo "  add <packages>       Add packages (npm install <packages>)"
        echo "  add-dev <packages>   Add dev packages (npm install --save-dev <packages>)"
        echo "  remove <packages>    Remove packages (npm uninstall <packages>)"
        echo ""
        echo "Development:"
        echo "  dev, start           Start development server (accessible on http://localhost:3000)"
        echo "  build                Build for production"
        echo "  test                 Run tests"
        echo "  lint                 Run linter"
        echo "  format               Format code with Prettier"
        echo ""
        echo "Direct Commands:"
        echo "  npm <args>           Run npm command directly"
        echo "  npx <args>           Run npx command directly"
        echo "  node <args>          Run node command directly"
        echo ""
        echo "Utilities:"
        echo "  shell, bash          Open interactive shell in Node.js container"
        echo "  clean                Remove node_modules and build artifacts"
        echo "  help                 Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0 init                              # Initialize React project"
        echo "  $0 install                           # Install dependencies"
        echo "  $0 add @tanstack/react-query         # Add React Query"
        echo "  $0 add-dev @types/node               # Add dev dependency"
        echo "  $0 dev                               # Start dev server"
        echo "  $0 npm run build                     # Build project"
        echo ""
        ;;
esac 