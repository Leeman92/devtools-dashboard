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
- React
- Vite
- TypeScript
- Tailwind CSS
- shadcn/ui

### Infrastructure
- Docker & Docker Compose
- Docker Swarm for production
- GitHub Actions for CI/CD
- HashiCorp Vault for secrets management
- Nginx for reverse proxy

## Prerequisites

- Docker and Docker Compose
- Node.js 20+
- PHP 8.4+
- Git
- HashiCorp Vault (for production)

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

# Frontend (if exists)
cp frontend/.env.example frontend/.env
```

3. Start the application:
```bash
# Development
docker compose up -d

# Or use the Makefile
make up
```

4. Access the application:
- Frontend: http://localhost:3000
- Backend API: http://localhost:8080

## Development

### Backend (Symfony)
```bash
cd backend
composer install
php bin/console cache:clear
```

### Using Makefile Commands
```bash
# Start all services
make up

# Stop all services
make down

# View logs
make logs

# Run tests
make test

# Build and deploy
make build
make deploy
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
- [Project Rules](.cursorrules) - Coding standards and best practices

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

1. Follow the coding standards defined in `.cursorrules`
2. Use semantic versioning for releases
3. All code must pass tests and security scans
4. Secrets must be managed through Vault
5. Follow the DevOps best practices outlined in the project rules

## License

MIT
