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

This guide covers the complete production deployment process for the DevTools Dashboard, including external MySQL setup, automated CI/CD deployment, and post-deployment configuration.

## 🎯 Overview

The production deployment uses:
- **External MySQL**: Standalone MariaDB container with Docker volumes
- **Docker Swarm**: Container orchestration for high availability
- **GitHub Actions**: Automated CI/CD pipeline
- **HashiCorp Vault**: Secure secrets management
- **Nginx**: SSL termination and reverse proxy

## 📋 Prerequisites

### Infrastructure Requirements
- Docker Swarm cluster initialized
- HashiCorp Vault server configured and accessible
- Domain name with DNS pointing to your server
- SSL certificate (Let's Encrypt recommended)

### Access Requirements
- SSH access to production server
- GitHub repository with Actions enabled
- Harbor container registry access
- Vault authentication credentials

### Environment Variables
```bash
export VAULT_ADDR="https://vault.patricklehmann.dev"
export VAULT_TOKEN="your-vault-token"
```

## 🚀 Deployment Process

### Phase 1: Infrastructure Setup

#### 1.1 Initialize Docker Swarm
```bash
# On your production server
docker swarm init

# Note the join token for additional nodes (if needed)
docker swarm join-token worker
```

#### 1.2 Set Up External MySQL
```bash
# Run the automated MySQL setup script
./scripts/deployment/setup-standalone-mysql.sh production
```

This script automatically:
- ✅ Generates secure 32-character passwords
- ✅ Creates MariaDB container with proper configuration
- ✅ Sets up `dashboard` database and user
- ✅ Stores all credentials in HashiCorp Vault
- ✅ Configures Docker networking for Swarm

#### 1.3 Configure Nginx (Optional)
If using external Nginx for SSL termination:
```bash
# Copy Nginx configuration
sudo cp nginx/dashboard.patricklehmann.dev.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/dashboard.patricklehmann.dev.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### Phase 2: Application Deployment

#### 2.1 Automated Deployment (Recommended)
The easiest way to deploy is via GitHub Actions:

```bash
# Commit and push your changes
git add .
git commit -m "feat: production deployment"
git push origin main
```

The GitHub Actions pipeline will:
1. **Build**: Create Docker images for backend and frontend
2. **Push**: Upload images to Harbor registry
3. **Deploy**: Deploy to production server via SSH
4. **Configure**: Generate environment files from Vault

#### 2.2 Manual Deployment (Alternative)
If you need to deploy manually:

```bash
# Generate environment file from Vault
./scripts/deployment/generate-env-file.sh

# Deploy the Docker stack
docker stack deploy -c docker-stack.yml dashboard

# Verify deployment
docker service ls
```

### Phase 3: Database Initialization

#### 3.1 Run Database Migrations
```bash
# Find the backend container
docker ps | grep backend

# Run Doctrine migrations
docker exec <backend-container-name> php bin/console doctrine:migrations:migrate --no-interaction

# Verify tables were created
docker exec dashboard-mysql mysql -u dashboard -p -e "USE dashboard; SHOW TABLES;"
```

#### 3.2 Create Initial User
```bash
# Interactive user creation
docker exec -it <backend-container-name> php bin/console app:create-user

# Non-interactive user creation
docker exec <backend-container-name> php bin/console app:create-user \
  --email="admin@dashboard.local" \
  --name="Admin User" \
  --password="secure-password" \
  --admin
```

### Phase 4: Verification

#### 4.1 Service Health Check
```bash
# Check all services are running
docker service ls

# Check service logs
docker service logs dashboard_dashboard-backend
docker service logs dashboard_dashboard-frontend

# Check MySQL connectivity
docker exec dashboard-mysql mysqladmin ping -u dashboard -p
```

#### 4.2 Application Access
- **Frontend**: https://dashboard.patricklehmann.dev
- **API Health**: https://dashboard.patricklehmann.dev/api/health
- **Login**: Use the credentials created in Phase 3.2

## 🔧 Configuration Details

### Docker Stack Services

```yaml
services:
  dashboard-frontend:
    image: harbor.patricklehmann.dev/dashboard/frontend:latest
    ports:
      - "3001:80"
    networks:
      - dashboard-network

  dashboard-backend:
    image: harbor.patricklehmann.dev/dashboard/backend:latest
    networks:
      - dashboard-network
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
    deploy:
      placement:
        constraints:
          - node.role == manager

networks:
  dashboard-network:
    external: true
```

### Environment Variables

All environment variables are managed through HashiCorp Vault:

```bash
# Core application settings
APP_ENV=prod
APP_SECRET=<generated-secret>
DATABASE_URL=mysql://dashboard:<password>@dashboard-mysql:3306/dashboard

# JWT Authentication
JWT_SECRET_KEY=<vault-managed>
JWT_PUBLIC_KEY=<vault-managed>
JWT_PASSPHRASE=<vault-managed>

# External services
HARBOR_USERNAME=<vault-managed>
HARBOR_PASSWORD=<vault-managed>
```

### Security Configuration

- **No exposed backend ports**: Backend only accessible via internal network
- **SSL termination**: Handled by external Nginx or Docker Swarm ingress
- **Secrets management**: All sensitive data stored in Vault
- **Container security**: Non-root users, read-only Docker socket

## 🛠️ Maintenance

### Regular Tasks

#### Daily
```bash
# Check service health
docker service ls

# Monitor logs for errors
docker service logs --tail=100 dashboard_dashboard-backend
```

#### Weekly
```bash
# Backup database
docker exec dashboard-mysql mysqldump -u root -p dashboard > backup-$(date +%Y%m%d).sql

# Check disk usage
df -h
docker system df
```

#### Monthly
```bash
# Update Docker images (via GitHub Actions)
git push origin main

# Rotate Vault tokens
vault auth -method=userpass username=your-username

# Review security logs
docker service logs dashboard_dashboard-backend | grep -i error
```

### Scaling

#### Horizontal Scaling
```bash
# Scale backend service
docker service scale dashboard_dashboard-backend=3

# Scale frontend service
docker service scale dashboard_dashboard-frontend=2
```

#### Resource Limits
```yaml
deploy:
  resources:
    limits:
      cpus: '1.0'
      memory: 512M
    reservations:
      cpus: '0.5'
      memory: 256M
```

## 🔍 Troubleshooting

### Common Issues

#### Service Won't Start
```bash
# Check service status
docker service ps dashboard_dashboard-backend --no-trunc

# Check logs
docker service logs dashboard_dashboard-backend

# Check node resources
docker node ls
docker node inspect <node-id>
```

#### Database Connection Issues
```bash
# Test MySQL connectivity
docker exec dashboard-mysql mysqladmin ping -u dashboard -p

# Check network connectivity
docker exec <backend-container> ping dashboard-mysql

# Verify Vault secrets
vault kv get secret/dashboard/production
```

#### SSL/Nginx Issues
```bash
# Check Nginx configuration
sudo nginx -t

# Check SSL certificate
openssl x509 -in /etc/letsencrypt/live/dashboard.patricklehmann.dev/fullchain.pem -text -noout

# Check Nginx logs
sudo tail -f /var/log/nginx/error.log
```

### Performance Issues

#### High Memory Usage
```bash
# Check container memory usage
docker stats

# Check MySQL memory usage
docker exec dashboard-mysql mysql -u root -p -e "SHOW STATUS LIKE 'Innodb_buffer_pool%';"
```

#### Slow Response Times
```bash
# Check application logs
docker service logs dashboard_dashboard-backend | grep -i slow

# Check MySQL slow queries
docker exec dashboard-mysql mysql -u root -p -e "SHOW VARIABLES LIKE 'slow_query_log';"
```

## 🔒 Security

### Security Checklist

- [ ] All secrets stored in Vault (no plaintext credentials)
- [ ] SSL/TLS enabled for all external communication
- [ ] Docker socket mounted read-only
- [ ] Services run as non-root users
- [ ] Network segmentation implemented
- [ ] Regular security updates applied
- [ ] Backup encryption enabled
- [ ] Access logs monitored

### Security Updates

```bash
# Update base images (triggers rebuild via GitHub Actions)
git commit -m "security: update base images" --allow-empty
git push origin main

# Update MySQL container
docker stop dashboard-mysql
docker rm dashboard-mysql
./scripts/deployment/setup-standalone-mysql.sh production
```

## 📊 Monitoring

### Health Checks

```bash
# Application health endpoint
curl -f https://dashboard.patricklehmann.dev/api/health

# Database health
docker exec dashboard-mysql mysqladmin ping -u dashboard -p

# Service health
docker service ls --filter "name=dashboard"
```

### Metrics Collection

Consider implementing:
- **Prometheus**: Metrics collection
- **Grafana**: Metrics visualization
- **Loki**: Log aggregation
- **Alertmanager**: Alert notifications

## 🔄 Rollback Procedures

### Application Rollback
```bash
# Rollback to previous image version
docker service update --image harbor.patricklehmann.dev/dashboard/backend:previous-tag dashboard_dashboard-backend

# Or redeploy from previous Git commit
git checkout <previous-commit>
git push origin main --force
```

### Database Rollback
```bash
# Restore from backup
docker exec -i dashboard-mysql mysql -u root -p dashboard < backup-YYYYMMDD.sql

# Rollback migrations (if needed)
docker exec <backend-container> php bin/console doctrine:migrations:migrate prev
```

## 📚 Additional Resources

- [MySQL External Setup Guide](MYSQL_EXTERNAL_SETUP.md)
- [Docker Swarm Documentation](https://docs.docker.com/engine/swarm/)
- [HashiCorp Vault Documentation](https://www.vaultproject.io/docs)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Nginx Configuration Guide](https://nginx.org/en/docs/) 