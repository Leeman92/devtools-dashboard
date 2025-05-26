# DevTools Dashboard

A full-stack web application for monitoring Docker containers and CI jobs with HashiCorp Vault integration for secrets management.

## Features

- Real-time Docker container monitoring
- CI job status tracking from GitHub
- Modern, responsive UI with Tailwind CSS
- RESTful API built with Symfony 7.2
- HashiCorp Vault integration for secure secrets management
- Docker Swarm deployment with high availability
- Comprehensive CI/CD pipeline with GitHub Actions

## Tech Stack

### Backend
- Symfony 7.2
- PHP 8.4+ with FrankenPHP
- MySQL 8.0
- Docker API integration via cURL with Unix socket support
- HashiCorp Vault for secrets management

### Frontend
- React 18 with TypeScript
- Vite 6.3.5 for fast development and optimized builds
- Tailwind CSS v3.4.0 with utility-first styling
- shadcn/ui components for accessible, modern UI
- Lucide React icons for consistent iconography
- Responsive design with mobile-first approach

### Infrastructure
- Docker & Docker Compose
- Docker Swarm for production
- GitHub Actions for CI/CD
- HashiCorp Vault for secrets management
- Nginx for reverse proxy

## Prerequisites

- Docker and Docker Compose
- Git
- HashiCorp Vault (for production)

**Note**: No local PHP, Node.js, or npm installation required! All development operations use Docker containers.

## Quick Start

1. Clone the repository:
```bash
git clone <repository-url>
cd devtools-dashboard
```

2. Set up environment variables:
```bash
# Backend
cp backend/.env.example backend/.env
# Frontend environment variables are handled via Vite proxy configuration
```

3. Start the full development environment:
```bash
# Start both backend and frontend
./scripts/dev.sh

# Or start services individually:
# Backend only
docker compose up -d

# Frontend only  
./scripts/docker-node.sh dev
```

4. Access the application:
- **Frontend Dashboard**: http://localhost:5173
- **Backend API**: http://localhost:80

## Development

### Full Stack Development
```bash
# Start both backend and frontend with one command
./scripts/dev.sh

# Check status of all services
./scripts/dev.sh status

# View logs from all services
./scripts/dev.sh logs

# Stop all services
./scripts/dev.sh stop
```

### Backend (Symfony)
```bash
# Use Docker-based PHP/Composer (no local installation required)
./scripts/docker-php.sh install
./scripts/docker-php.sh console cache:clear
./scripts/docker-php.sh console doctrine:migrations:migrate
```

### Frontend (React + TypeScript)
```bash
# Use Docker-based Node.js/npm (no local installation required)
./scripts/docker-node.sh install
./scripts/docker-node.sh dev
./scripts/docker-node.sh build
```

### Using Makefile Commands (Legacy)
```bash
# Start backend services only
make up

# Stop all services
make down

# View backend logs
make logs

# Run tests
make test
```

## Project Structure

```
devtools-dashboard/
├── backend/              # Symfony 7.2 application
│   ├── .docker/         # Docker configuration
│   ├── src/             # Application source code
│   ├── config/          # Symfony configuration
│   ├── public/          # Web root
│   ├── var/             # Cache, logs, sessions
│   └── tests/           # Test suites
├── .github/workflows/   # CI/CD pipelines
├── docs/               # Documentation
├── nginx/              # Nginx configuration
├── scripts/            # Utility scripts
├── docker-stack.yml    # Docker Swarm configuration
├── docker-compose.yml  # Development environment
└── Makefile           # Development commands
```

## Documentation

- [Development Guide](docs/DEVELOPMENT.md) - Comprehensive development workflow and standards
- [Deployment Guide](docs/DEPLOYMENT.md) - Multi-environment deployment procedures
- [Docker Socket Access Guide](DOCKER_SOCKET_ACCESS.md) - Docker integration configuration and troubleshooting
- [Vault Setup Guide](docs/vault-setup.md) - HashiCorp Vault configuration
- [Vault Secrets Template](docs/vault-secrets-template.md) - Secrets management template
- [Project Rules](.cursorrules) - Full-stack coding standards and best practices
- [TODO & Status](TODO.md) - Current project status and roadmap

## Security

This project follows security best practices:
- All secrets managed through HashiCorp Vault
- No hardcoded credentials in code
- Container security with non-root users
- HTTPS everywhere in production
- Regular security updates and vulnerability scanning

## Testing

### Backend
```bash
cd backend
php bin/phpunit
```

### Using Makefile
```bash
make test
```

## Deployment

### Development
```bash
docker compose up -d
```

### Production (Docker Swarm)
```bash
docker stack deploy -c docker-stack.yml devtools-dashboard
```

See the [deployment documentation](docs/vault-setup.md) for detailed production setup instructions.

## Contributing

1. Follow the full-stack coding standards defined in `.cursorrules`
2. Use Docker-based development workflow (no local dependencies required)
3. Use semantic versioning for releases
4. All code must pass tests and security scans (both backend and frontend)
5. Secrets must be managed through Vault
6. Follow the DevOps and frontend best practices outlined in the project rules
7. Ensure TypeScript strict mode compliance and accessibility standards
8. Test responsive design on multiple screen sizes

## License

MIT
