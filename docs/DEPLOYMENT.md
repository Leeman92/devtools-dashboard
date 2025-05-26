# Deployment Guide

This guide covers deployment procedures for the DevTools Dashboard project across different environments.

## Table of Contents

- [Overview](#overview)
- [Environment Configuration](#environment-configuration)
- [Development Deployment](#development-deployment)
- [Staging Deployment](#staging-deployment)
- [Production Deployment](#production-deployment)
- [CI/CD Pipeline](#cicd-pipeline)
- [Monitoring and Health Checks](#monitoring-and-health-checks)
- [Rollback Procedures](#rollback-procedures)
- [Troubleshooting](#troubleshooting)

## Overview

The DevTools Dashboard uses a multi-environment deployment strategy:

- **Development**: Local Docker Compose setup
- **Staging**: Mirror of production environment for testing
- **Production**: Docker Swarm with high availability and load balancing

### Deployment Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Development   │    │     Staging     │    │   Production    │
│                 │    │                 │    │                 │
│ Docker Compose  │    │ Docker Swarm    │    │ Docker Swarm    │
│ Single Node     │    │ Single Node     │    │ Multi-Node      │
│ Local Secrets   │    │ Vault Secrets   │    │ Vault Secrets   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Environment Configuration

### Environment Variables

Each environment requires specific configuration:

#### Development (.env.local)
```bash
# Application
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=your-dev-secret

# Database
DATABASE_URL=mysql://user:password@database:3306/devtools_dashboard

# Vault (optional for development)
VAULT_ADDR=http://localhost:8200
VAULT_TOKEN=dev-token
```

#### Staging/Production
```bash
# Application
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=${VAULT_SECRET}

# Database
DATABASE_URL=${VAULT_DATABASE_URL}

# Vault
VAULT_ADDR=https://vault.yourdomain.com
VAULT_ROLE_ID=${VAULT_ROLE_ID}
VAULT_SECRET_ID=${VAULT_SECRET_ID}
```

## Development Deployment

### Prerequisites

- Docker and Docker Compose
- Make (optional, for convenience commands)

### Quick Start

1. Clone the repository:
```bash
git clone <repository-url>
cd devtools-dashboard
```

2. Start the development environment:
```bash
# Using Make
make up

# Or using Docker Compose directly
docker compose up -d
```

3. Verify deployment:
```bash
# Check container status
docker compose ps

# View logs
make logs

# Test API endpoint
curl http://localhost:8080/api/health
```

### Development Commands

```bash
# Environment management
make up              # Start all services
make down            # Stop all services
make restart         # Restart all services
make logs            # View logs

# Development
make install         # Install dependencies
make login-backend   # Access backend container
make build           # Rebuild containers

# Testing
make test            # Run tests
make test-coverage   # Generate coverage report
```

## Staging Deployment

Staging environment mirrors production but uses a single node setup.

### Prerequisites

- Docker Swarm initialized
- HashiCorp Vault configured
- SSL certificates
- Domain name configured

### Deployment Steps

1. **Initialize Docker Swarm** (if not already done):
```bash
docker swarm init
```

2. **Configure Vault secrets** (see [Vault Setup Guide](vault-setup.md)):
```bash
# Set up Vault secrets for staging
vault kv put secret/staging/dashboard \
  APP_SECRET="staging-secret" \
  DATABASE_URL="mysql://user:pass@db:3306/dashboard_staging"
```

3. **Deploy the stack**:
```bash
# Set environment to staging
export ENVIRONMENT=staging

# Deploy using Docker Stack
docker stack deploy -c docker-stack.yml devtools-dashboard-staging
```

4. **Verify deployment**:
```bash
# Check service status
docker service ls

# Check service logs
docker service logs devtools-dashboard-staging_backend

# Test health endpoint
curl https://staging.yourdomain.com/api/health
```

## Production Deployment

Production uses Docker Swarm with multiple nodes for high availability.

### Prerequisites

- Multi-node Docker Swarm cluster
- Load balancer (external or Docker Swarm routing mesh)
- HashiCorp Vault cluster
- SSL certificates
- Monitoring and logging infrastructure

### Vault Setup

Before deployment, configure all required secrets in HashiCorp Vault:

```bash
# Interactive setup of all required secrets
./scripts/deployment/setup-vault-secrets.sh production

# Or manually configure secrets
vault kv put secret/dashboard/production \
  APP_SECRET="$(openssl rand -hex 32)" \
  DATABASE_URL="mysql://dashboard_user:secure_password@mariadb:3306/dashboard" \
  DOCKER_SOCKET_PATH="/var/run/docker.sock" \
  GITHUB_TOKEN="ghp_your_personal_access_token_here" \
  GITHUB_API_URL="https://api.github.com" \
  PROMETHEUS_URL="http://prometheus:9090" \
  GRAFANA_URL="http://grafana:3000"
```

### Pre-deployment Checklist

- [ ] All secrets configured in Vault: `./scripts/deployment/setup-vault-secrets.sh production`
- [ ] Environment file generated and validated: `./scripts/deployment/generate-env-file.sh`
- [ ] SSL certificates valid and configured
- [ ] Database migrations tested
- [ ] Docker socket access configured for production
- [ ] Backup procedures verified
- [ ] Monitoring alerts configured
- [ ] Rollback plan prepared
- [ ] Pre-commit validation passes: `./.githooks/pre-commit`

### Deployment Steps

1. **Prepare the environment**:
```bash
# Set production environment
export ENVIRONMENT=production

# Verify Vault connectivity
vault status

# Check Docker Swarm status
docker node ls
```

2. **Run database migrations** (if needed):
```bash
# Connect to a manager node
docker run --rm \
  --network devtools-dashboard_backend \
  -e DATABASE_URL="${DATABASE_URL}" \
  harbor.patricklehmann.dev/dashboard/dashboard:latest \
  php bin/console doctrine:migrations:migrate --no-interaction
```

3. **Deploy the application**:
```bash
# Deploy with zero-downtime rolling update
docker stack deploy -c docker-stack.yml devtools-dashboard

# Monitor deployment progress
watch docker service ls
```

4. **Verify deployment**:
```bash
# Check all services are running
docker service ls

# Verify health checks
curl https://yourdomain.com/api/health

# Check application logs
docker service logs devtools-dashboard_backend
```

### Production Configuration

#### docker-stack.yml (Production)
```yaml
version: '3.8'

services:
  backend:
    image: harbor.patricklehmann.dev/dashboard/dashboard:${VERSION:-latest}
    deploy:
      replicas: 3
      update_config:
        parallelism: 1
        delay: 10s
        failure_action: rollback
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
    environment:
      - APP_ENV=prod
      - VAULT_ADDR=${VAULT_ADDR}
      - VAULT_ROLE_ID=${VAULT_ROLE_ID}
      - VAULT_SECRET_ID=${VAULT_SECRET_ID}
    networks:
      - backend
      - frontend
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/api/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  nginx:
    image: nginx:alpine
    deploy:
      replicas: 2
      update_config:
        parallelism: 1
        delay: 10s
    ports:
      - "80:80"
      - "443:443"
    networks:
      - frontend
    configs:
      - source: nginx_config
        target: /etc/nginx/nginx.conf

networks:
  backend:
    driver: overlay
  frontend:
    driver: overlay

configs:
  nginx_config:
    external: true
```

## CI/CD Pipeline

The project uses GitHub Actions for automated deployment.

### Pipeline Stages

1. **Code Quality Checks**
   - Linting (PHP CS Fixer)
   - Static analysis (PHPStan)
   - Security scanning

2. **Testing**
   - Unit tests
   - Integration tests
   - Coverage reporting

3. **Build**
   - Docker image build
   - Multi-stage optimization
   - Security scanning

4. **Deploy**
   - Staging deployment (automatic on main branch)
   - Production deployment (manual approval)

### Deployment Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]
  release:
    types: [published]

jobs:
  deploy-staging:
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to Staging
        run: |
          # Automated staging deployment
          
  deploy-production:
    if: github.event_name == 'release'
    runs-on: ubuntu-latest
    environment: production
    steps:
      - name: Deploy to Production
        run: |
          # Production deployment with approval
```

## Monitoring and Health Checks

### Health Check Endpoints

```php
// src/Controller/HealthController.php
#[Route('/api/health', name: 'health_check', methods: ['GET'])]
public function healthCheck(): JsonResponse
{
    return $this->json([
        'status' => 'healthy',
        'timestamp' => time(),
        'version' => $this->getParameter('app.version'),
        'environment' => $this->getParameter('kernel.environment'),
    ]);
}
```

### Monitoring Stack

- **Application Metrics**: Symfony Profiler, custom metrics
- **Infrastructure Metrics**: Docker stats, system metrics
- **Logging**: Centralized logging with structured data
- **Alerting**: Critical error notifications

### Health Check Commands

```bash
# Check application health
curl https://yourdomain.com/api/health

# Check Docker service health
docker service ps devtools-dashboard_backend

# Check container health
docker ps --filter "health=unhealthy"
```

## Rollback Procedures

### Automatic Rollback

Docker Swarm can automatically rollback failed deployments:

```yaml
deploy:
  update_config:
    failure_action: rollback
    monitor: 60s
```

### Manual Rollback

1. **Identify the previous version**:
```bash
# List recent deployments
docker service ls
docker service ps devtools-dashboard_backend
```

2. **Rollback to previous version**:
```bash
# Rollback service
docker service rollback devtools-dashboard_backend

# Or deploy specific version
docker service update --image harbor.patricklehmann.dev/dashboard/dashboard:v1.2.3 devtools-dashboard_backend
```

3. **Verify rollback**:
```bash
# Check service status
docker service ps devtools-dashboard_backend

# Verify application health
curl https://yourdomain.com/api/health
```

### Database Rollback

If database migrations need to be rolled back:

```bash
# Connect to database container
docker exec -it $(docker ps -q -f name=database) mysql -u root -p

# Or use Doctrine migrations
docker run --rm \
  --network devtools-dashboard_backend \
  -e DATABASE_URL="${DATABASE_URL}" \
  harbor.patricklehmann.dev/dashboard/dashboard:v1.2.3 \
  php bin/console doctrine:migrations:migrate prev --no-interaction
```

## Troubleshooting

### Common Deployment Issues

1. **Service won't start**:
```bash
# Check service logs
docker service logs devtools-dashboard_backend

# Check node resources
docker node ls
docker system df
```

2. **Health check failures**:
```bash
# Check container health
docker ps --filter "health=unhealthy"

# Inspect health check logs
docker inspect <container_id>
```

3. **Vault connection issues**:
```bash
# Test Vault connectivity
vault status

# Check Vault authentication
vault auth -method=approle role_id=${VAULT_ROLE_ID} secret_id=${VAULT_SECRET_ID}
```

4. **Database connection issues**:
```bash
# Check database service
docker service ps devtools-dashboard_database

# Test database connection
docker run --rm --network devtools-dashboard_backend mysql:8.0 \
  mysql -h database -u root -p -e "SELECT 1"
```

### Performance Issues

1. **High CPU usage**:
```bash
# Check container stats
docker stats

# Scale services if needed
docker service scale devtools-dashboard_backend=5
```

2. **Memory issues**:
```bash
# Check memory usage
docker system df
docker stats --no-stream

# Restart services if needed
docker service update --force devtools-dashboard_backend
```

### Logging and Debugging

```bash
# Application logs
docker service logs -f devtools-dashboard_backend

# System logs
journalctl -u docker.service

# Container inspection
docker inspect <container_id>
```

## Security Considerations

### Deployment Security

- Use specific image tags, not `latest`
- Scan images for vulnerabilities
- Use non-root users in containers
- Implement network segmentation
- Regular security updates

### Secrets Management

- Never commit secrets to version control
- Use Vault for all sensitive data
- Rotate secrets regularly
- Implement least privilege access
- Monitor secret access

### Network Security

- Use encrypted communication (TLS)
- Implement proper firewall rules
- Use Docker secrets for sensitive data
- Regular security audits

## Additional Resources

- [Development Guide](DEVELOPMENT.md)
- [Vault Setup Guide](vault-setup.md)
- [Docker Swarm Documentation](https://docs.docker.com/engine/swarm/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions) 