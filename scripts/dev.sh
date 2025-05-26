#!/bin/bash

# DevTools Dashboard - Development Environment Starter
# Starts both backend and frontend services for development

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

# Ensure we're in the project root
if [ ! -f "docker-compose.yml" ]; then
    print_status $RED "❌ Error: Must be run from project root (where docker-compose.yml exists)"
    exit 1
fi

print_status $BLUE "🚀 Starting DevTools Dashboard Development Environment"
echo ""

# Function to cleanup on exit
cleanup() {
    print_status $YELLOW "🛑 Shutting down development environment..."
    docker-compose down
    pkill -f "devtools-frontend-dev" 2>/dev/null || true
    exit 0
}

# Set up cleanup on script exit
trap cleanup SIGINT SIGTERM

case "${1:-start}" in
    "start")
        print_status $YELLOW "📋 Starting backend services..."
        docker-compose up -d
        
        print_status $GREEN "✅ Backend services started"
        print_status $BLUE "🌐 Backend API: http://localhost:80"
        echo ""
        
        print_status $YELLOW "📋 Starting frontend development server..."
        ./scripts/docker-node.sh dev &
        FRONTEND_PID=$!
        
        print_status $GREEN "✅ Frontend development server started"
        print_status $BLUE "🌐 Frontend: http://localhost:5173"
        echo ""
        
        print_status $GREEN "🎉 Development environment is ready!"
        print_status $YELLOW "Press Ctrl+C to stop all services"
        echo ""
        
        # Wait for frontend process
        wait $FRONTEND_PID
        ;;
    
    "stop")
        print_status $YELLOW "🛑 Stopping development environment..."
        docker-compose down
        pkill -f "devtools-frontend-dev" 2>/dev/null || true
        print_status $GREEN "✅ Development environment stopped"
        ;;
    
    "restart")
        print_status $YELLOW "🔄 Restarting development environment..."
        $0 stop
        sleep 2
        $0 start
        ;;
    
    "status")
        print_status $BLUE "📊 Development Environment Status"
        echo ""
        
        print_status $YELLOW "Backend Services:"
        docker-compose ps
        echo ""
        
        print_status $YELLOW "Frontend Process:"
        if pgrep -f "devtools-frontend-dev" > /dev/null; then
            print_status $GREEN "✅ Frontend development server is running"
        else
            print_status $RED "❌ Frontend development server is not running"
        fi
        echo ""
        
        print_status $YELLOW "Available URLs:"
        print_status $BLUE "🌐 Frontend: http://localhost:5173"
        print_status $BLUE "🌐 Backend API: http://localhost:80"
        print_status $BLUE "🌐 API Docs: http://localhost:80/api"
        ;;
    
    "logs")
        if [ "$2" = "frontend" ]; then
            print_status $YELLOW "📋 Frontend logs (press Ctrl+C to exit):"
            docker logs -f devtools-frontend-dev 2>/dev/null || print_status $RED "❌ Frontend container not found"
        elif [ "$2" = "backend" ]; then
            print_status $YELLOW "📋 Backend logs (press Ctrl+C to exit):"
            docker-compose logs -f backend
        else
            print_status $YELLOW "📋 All logs (press Ctrl+C to exit):"
            docker-compose logs -f &
            docker logs -f devtools-frontend-dev 2>/dev/null &
            wait
        fi
        ;;
    
    "help"|*)
        echo "DevTools Dashboard - Development Environment Manager"
        echo ""
        echo "Usage: $0 <command>"
        echo ""
        echo "Commands:"
        echo "  start      Start both backend and frontend services (default)"
        echo "  stop       Stop all development services"
        echo "  restart    Restart all development services"
        echo "  status     Show status of all services"
        echo "  logs       Show logs from all services"
        echo "  logs frontend   Show only frontend logs"
        echo "  logs backend    Show only backend logs"
        echo "  help       Show this help message"
        echo ""
        echo "URLs:"
        echo "  Frontend:    http://localhost:5173"
        echo "  Backend API: http://localhost:80"
        echo ""
        echo "Individual service management:"
        echo "  Backend:     docker-compose up/down"
        echo "  Frontend:    ./scripts/docker-node.sh dev"
        echo ""
        ;;
esac 