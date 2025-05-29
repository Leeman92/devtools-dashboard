# DevTools Dashboard

A full-stack web application for monitoring Docker containers and CI jobs with HashiCorp Vault integration for secrets management.

## ✅ Current Status: **FULLY WORKING APPLICATION**

This is a complete, working full-stack application with:
- **Real-time Docker container monitoring** with live status updates
- **Beautiful React + TypeScript frontend** with modular component architecture
- **JWT-based authentication system** with login/logout
- **Docker-first development environment** with hot reload
- **Production-ready deployment** with Docker Swarm

## Features

- ✅ **Real-time Docker container monitoring** - Live status updates every 5 seconds
- ✅ **Modern, responsive UI** - React 18 + TypeScript with Tailwind CSS v3.4.0
- ✅ **Modular component architecture** - Clean, maintainable frontend structure
- ✅ **Authentication system** - JWT-based login/logout functionality
- ✅ **RESTful API** - Built with Symfony 7.2 and Docker API integration
- ✅ **Development environment** - Docker-first with hot reload for both frontend and backend
- 🔄 **CI job status tracking** - GitHub Actions integration (planned)
- 🔄 **HashiCorp Vault integration** - Secure secrets management (configured)
- ✅ **Docker Swarm deployment** - Production-ready with high availability

## Tech Stack

### Frontend
- **React 18** with TypeScript and modern component patterns
- **Vite 6.3.5** for fast development and optimized builds
- **Tailwind CSS v3.4.0** with utility-first styling
- **shadcn/ui components** for accessible, modern UI
- **Lucide React icons** for consistent iconography
- **Modular architecture** with organized component structure

### Backend
- **Symfony 7.2** with PHP 8.4+ and FrankenPHP
- **MariaDB 10.11** (external standalone container)
- **Docker API integration** via cURL with Unix socket support
- **HashiCorp Vault** for secrets management
- **Doctrine ORM** with migrations

### Infrastructure
- **Docker & Docker Compose** for containerization
- **Docker Swarm** for production deployment
- **External MySQL** container with Docker volumes
- **GitHub Actions** for automated CI/CD
- **HashiCorp Vault** for secrets management
- **Nginx** for SSL termination and reverse proxy

## Prerequisites

- Docker and Docker Compose
- Git
- HashiCorp Vault (for production)
- Access to Docker Swarm cluster (for production)

**Note**: No local PHP, Node.js, or npm installation required! All development operations use Docker containers.

## Quick Start

1. **Clone and Setup**:
```bash
git clone <repository-url>
cd devtools-dashboard

# Validate your environment
./scripts/validate-setup.sh
```

2. **Start Development Environment**:
```bash
# Start both backend and frontend (recommended)
./scripts/dev.sh

# Access the application:
# - Frontend Dashboard: http://localhost:5173
# - Backend API: http://localhost:80
# - Database: MariaDB on localhost:3306
```

3. **What You'll See**:
- **Modern Dashboard**: Beautiful gradient cards showing container status
- **Real-time Updates**: Live container monitoring with 5-second refresh
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Authentication**: Login/logout functionality with JWT tokens
- **Navigation**: Sidebar with Dashboard, Containers, CI/CD, Repositories tabs

## 📚 Documentation

### 📖 **[Complete Documentation Index](docs/README.md)**

Our documentation is organized in the `docs/` directory with comprehensive guides for all aspects of the project:

| Category | Document | Description |
|----------|----------|-------------|
| **Development** | [Development Guide](docs/DEVELOPMENT.md) | Complete setup and workflow |
| | [Frontend Architecture](docs/FRONTEND_ARCHITECTURE.md) | Component structure and patterns |
| **Deployment** | [Deployment Guide](docs/DEPLOYMENT.md) | Production deployment procedures |
| | [MySQL External Setup](docs/MYSQL_EXTERNAL_SETUP.md) | Database configuration |
| **Security** | [Security & Authentication](docs/SECURITY_ENDPOINTS.md) | JWT and security practices |
| | [Vault Setup](docs/vault-setup.md) | Secrets management configuration |
| **Operations** | [Logging Setup](docs/LOGGING_SETUP.md) | Centralized logging configuration |

### Quick Reference Links
- **New to the project?** Start with [Development Guide](docs/DEVELOPMENT.md)
- **Frontend development?** Check [Frontend Architecture](docs/FRONTEND_ARCHITECTURE.md)
- **Deploying to production?** Follow [Deployment Guide](docs/DEPLOYMENT.md)
- **Security questions?** Review [Security Documentation](docs/SECURITY_ENDPOINTS.md)
- **Need quick commands?** Use [Quick Reference](docs/QUICK_REFERENCE.md)

## Development Workflow

### Full Stack Development
```bash
# Start both backend and frontend
./scripts/dev.sh

# Check status of all services
./scripts/dev.sh status

# View logs from all services
./scripts/dev.sh logs

# Stop all services
./scripts/dev.sh stop
```

### Frontend Development (React + TypeScript)
```bash
# Install dependencies
./scripts/docker-node.sh install

# Start development server
./scripts/docker-node.sh dev

# Build for production
./scripts/docker-node.sh build

# Type checking
./scripts/docker-node.sh npx tsc --noEmit
```

### Backend Development (Symfony)
```bash
# Install dependencies
./scripts/docker-php.sh install

# Run database migrations
./scripts/docker-php.sh console doctrine:migrations:migrate

# Clear cache
./scripts/docker-php.sh console cache:clear

# Validate configuration
./scripts/docker-php.sh validate
```

## Project Structure

```
devtools-dashboard/
├── docs/                    # 📚 Comprehensive documentation
│   ├── README.md           # Documentation index
│   ├── DEVELOPMENT.md      # Development guide
│   ├── FRONTEND_ARCHITECTURE.md # Component architecture
│   ├── DEPLOYMENT.md       # Deployment procedures
│   └── ...                 # Additional guides
├── frontend/               # React TypeScript application
│   ├── src/
│   │   ├── components/     # Modular component structure
│   │   │   ├── auth/      # Authentication components
│   │   │   ├── dashboard/ # Dashboard components
│   │   │   ├── layout/    # Layout components
│   │   │   └── ui/        # Base UI components
│   │   ├── types/         # TypeScript definitions
│   │   └── App.tsx        # Root component
├── backend/               # Symfony 7.2 application
│   ├── src/              # Application source
│   ├── config/           # Configuration
│   └── tests/            # Test suites
├── scripts/              # Development utilities
├── .github/workflows/    # CI/CD pipelines
└── docker-compose.yml    # Development environment
```

## Component Architecture

The frontend features a clean, modular architecture:

```
App.tsx (Root)
├── AuthProvider (Context)
├── ProtectedRoute (Auth Guard)
└── Layout (Main Structure)
    ├── Navbar (Sidebar Navigation)
    └── Dashboard (Content Router)
        ├── StatsCards (Overview Metrics)
        ├── ContainersList (Docker Management)
        ├── CPUChart (Performance Visualization)
        └── TabContent (Feature Placeholders)
```

**Key Features:**
- **TypeScript**: Strict typing for all components
- **Modern React**: Functional components with hooks
- **Reusable Components**: Modular, maintainable structure
- **Responsive Design**: Mobile-first with Tailwind CSS
- **Real-time Updates**: Efficient polling with cleanup

For detailed component documentation, see [Frontend Architecture](docs/FRONTEND_ARCHITECTURE.md).

## Security

This project follows security best practices:
- ✅ **Secrets Management**: All secrets managed through HashiCorp Vault
- ✅ **No Hardcoded Credentials**: Zero secrets in code or configuration
- ✅ **Container Security**: Non-root users and minimal attack surface
- ✅ **HTTPS Everywhere**: SSL/TLS in production environments
- ✅ **Regular Updates**: Automated security updates and vulnerability scanning
- ✅ **JWT Authentication**: Stateless, secure authentication system

For complete security documentation, see [Security & Authentication](docs/SECURITY_ENDPOINTS.md).

## Contributing

1. **Read the Documentation**: Start with [Development Guide](docs/DEVELOPMENT.md)
2. **Follow Code Standards**: Review [Frontend Architecture](docs/FRONTEND_ARCHITECTURE.md)
3. **Test Your Changes**: Use `./scripts/validate-setup.sh`
4. **Update Documentation**: Keep docs in sync with code changes

## Support

- **Development Issues**: Check [Development Guide](docs/DEVELOPMENT.md) troubleshooting
- **Deployment Problems**: Review [Deployment Guide](docs/DEPLOYMENT.md) procedures
- **Architecture Questions**: Consult [Frontend Architecture](docs/FRONTEND_ARCHITECTURE.md)
- **Security Concerns**: Review [Security Documentation](docs/SECURITY_ENDPOINTS.md)

---

**📚 For complete documentation, visit [docs/README.md](docs/README.md)**
