# DevTools Dashboard - Quick Reference

## 🎉 **FULLY WORKING APPLICATION** - Quick Start

```bash
# 1. Start the complete development environment
./scripts/dev.sh

# 2. Access the application
# Frontend: http://localhost:5173 (Beautiful React dashboard)
# Backend API: http://localhost:80 (Symfony with Docker integration)

# 3. Fix container conflicts if needed
docker stop devtools-frontend-dev && docker rm devtools-frontend-dev
./scripts/docker-node.sh dev

# 4. Verify everything works
curl http://localhost:80/api/docker/containers  # Should return actual container data
```

## 🚀 **Working Features**

- ✅ **Real-time Docker monitoring** - Live container status updates
- ✅ **Beautiful React dashboard** - Modern UI with Tailwind CSS
- ✅ **Authentication system** - JWT-based login/logout
- ✅ **Hot reload development** - Both frontend and backend auto-update
- ✅ **Docker API integration** - Full container monitoring capabilities

## 🐳 Docker Wrapper Script

Use `./scripts/docker-php.sh` for simplified Docker commands:

```bash
# Common commands
./scripts/docker-php.sh validate          # Validate composer files
./scripts/docker-php.sh install           # Install dependencies
./scripts/docker-php.sh update            # Update dependencies
./scripts/docker-php.sh create-db         # Create database
./scripts/docker-php.sh migrate           # Run migrations
./scripts/docker-php.sh collect-metrics   # Collect metrics (dry-run)
./scripts/docker-php.sh test              # Run tests

# Advanced usage
./scripts/docker-php.sh console doctrine:migrations:status
./scripts/docker-php.sh composer require symfony/cache
./scripts/docker-php.sh collect-metrics --source=docker
```

## 🔧 Common Commands

### Dependency Management (Docker)
```bash
# Using wrapper script (recommended)
./scripts/docker-php.sh validate                    # Validate composer files
./scripts/docker-php.sh composer require package   # Add new dependency
./scripts/docker-php.sh update                      # Update dependencies (includes validation)

# Direct Docker commands (if needed)
docker run --rm -v $(pwd):/app -w /app composer:latest validate
docker run --rm -v $(pwd):/app -w /app composer:latest require package/name
docker run --rm -v $(pwd):/app -w /app composer:latest update
```

### Database Operations (Docker)
```bash
# Using wrapper script (recommended)
./scripts/docker-php.sh create-db                           # Create database
./scripts/docker-php.sh console doctrine:migrations:diff    # Generate migration
./scripts/docker-php.sh migrate                             # Run migrations
./scripts/docker-php.sh console doctrine:migrations:status  # Check migration status

# Direct Docker commands (if needed)
docker run --rm -v $(pwd):/app -w /app --network host php:8.4-cli php bin/console doctrine:database:create
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php bin/console doctrine:migrations:diff
docker run --rm -v $(pwd):/app -w /app --network host php:8.4-cli php bin/console doctrine:migrations:migrate
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php bin/console doctrine:migrations:status
```

### Data Collection (Docker)
```bash
# Using wrapper script (recommended)
./scripts/docker-php.sh collect-metrics                              # Dry run by default
./scripts/docker-php.sh collect-metrics --source=docker             # Collect Docker metrics
./scripts/docker-php.sh collect-metrics --source=github --repository=owner/repo  # GitHub metrics

# Direct Docker commands (if needed)
docker run --rm -v $(pwd):/app -w /app --network host php:8.4-cli php bin/console app:collect-metrics
docker run --rm -v $(pwd):/app -w /app --network host php:8.4-cli php bin/console app:collect-metrics --source=docker
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php bin/console app:collect-metrics --dry-run
```

### Docker Operations
```bash
# Build image
docker build -f backend/.docker/Dockerfile backend/

# Build with specific target
docker build -f backend/.docker/Dockerfile --target=development backend/

# Run container locally
docker run -p 8000:80 your-image-name
```

### Deployment Operations
```bash
# Setup Vault secrets (interactive)
./scripts/deployment/setup-vault-secrets.sh production

# Generate environment file from Vault
./scripts/deployment/generate-env-file.sh

# Validate environment file
grep -c "^[A-Z_].*=" .env.production

# Deploy to staging
docker stack deploy -c docker-stack.yml devtools-dashboard-staging

# Deploy to production
docker stack deploy -c docker-stack.yml devtools-dashboard
```

## 🐛 Troubleshooting

### Composer Issues
| Problem | Solution |
|---------|----------|
| Exit code 4 during build | `./scripts/docker-php.sh update` |
| Lock file out of sync | `./scripts/docker-php.sh update` |
| Invalid composer.json | `./scripts/docker-php.sh validate` |

### Docker Issues
| Problem | Solution |
|---------|----------|
| Permission denied | Check user/group in Dockerfile |
| Build cache issues | `docker build --no-cache` |
| Socket access denied | Ensure Docker socket is mounted in docker-compose.yml/docker-stack.yml |
| Docker API errors | Check DOCKER_SOCKET_PATH and socket mount |
| Swarm API unavailable | Ensure containers run on manager nodes (docker-stack.yml) |

### Database Issues
| Problem | Solution |
|---------|----------|
| Connection refused | Check DATABASE_URL in .env |
| Migration errors | `docker run --rm -v $(pwd):/app -w /app php:8.4-cli php bin/console doctrine:migrations:status` |
| Schema out of sync | `docker run --rm -v $(pwd):/app -w /app php:8.4-cli php bin/console doctrine:migrations:diff` |

### API Issues
| Problem | Solution |
|---------|----------|
| 404 errors | Check route configuration |
| 500 errors | Check logs in `var/log/` |
| GitHub API errors | Verify GITHUB_TOKEN |
| Docker API errors | Check DOCKER_SOCKET_PATH |

## 📊 API Quick Reference

### Health Checks
- `GET /health` - Application health
- `GET /api/infrastructure/health` - Infrastructure health

### Docker Monitoring
- `GET /api/docker/services` - All services
- `GET /api/docker/containers` - All containers
- `GET /api/docker/services/{id}/logs` - Service logs

### GitHub CI/CD
- `GET /api/github/{owner}/{repo}/runs` - Recent runs
- `GET /api/github/{owner}/{repo}/stats` - Statistics
- `GET /api/github/{owner}/{repo}/history` - Historical data

### Infrastructure
- `GET /api/infrastructure/metrics` - All metrics
- `GET /api/infrastructure/metrics/latest` - Latest values
- `GET /api/infrastructure/metrics/chart/{source}/{metric}` - Chart data

## 🔐 Environment Variables

### Required
```bash
DATABASE_URL="mysql://user:pass@host:3306/db"
DOCKER_SOCKET_PATH="/var/run/docker.sock"  # Must match mounted socket path
GITHUB_TOKEN="ghp_your_token_here"
```

### Optional
```bash
GITHUB_API_URL="https://api.github.com"
PROMETHEUS_URL="http://localhost:9090"
GRAFANA_URL="http://localhost:3000"
```

## 📝 Development Workflow

1. **Before making changes**:
   ```bash
   ./scripts/validate-setup.sh
   ```

2. **After adding dependencies**:
   ```bash
   ./scripts/docker-php.sh update
   ```

3. **Before committing**:
   ```bash
   ./scripts/docker-php.sh validate
   ./scripts/docker-php.sh console lint:container
   ```

4. **Test Docker socket access** (if using Docker monitoring):
   ```bash
   # Test socket access locally
   docker-compose up -d
   curl http://localhost/api/docker/services
   
   # Test socket mount
   docker-compose exec backend ls -la /var/run/docker.sock
   ```

5. **Before deploying**:
   ```bash
   # Setup Vault secrets
   ./scripts/deployment/setup-vault-secrets.sh production
   
   # Generate environment file
   ./scripts/deployment/generate-env-file.sh
   
   # Validate everything
   ./scripts/validate-setup.sh
   
   # Test Docker build
   docker build -f backend/.docker/Dockerfile backend/
   ```

## 🎯 Performance Tips

- Use `--no-dev` for production composer installs
- Enable OPcache in production
- Use Docker layer caching
- Monitor database query performance
- Implement proper indexing for time-series data

## 📚 Useful Links

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Docker API Reference](https://docs.docker.com/engine/api/)
- [GitHub API Documentation](https://docs.github.com/en/rest)

## 🚀 **Quick Start Commands**

### **Full Development Environment**
```bash
# Start everything (backend + frontend + database)
./scripts/dev.sh

# Fix container conflicts if needed
docker stop devtools-frontend-dev && docker rm devtools-frontend-dev
./scripts/docker-node.sh dev

# Validate complete setup
./scripts/validate-setup.sh
```

### **Backend Commands** (PHP/Symfony)
```bash
# Dependency Management
./scripts/docker-php.sh install            # Install composer dependencies
./scripts/docker-php.sh update             # Update dependencies
./scripts/docker-php.sh validate           # Validate composer.json/lock

# Database Operations
./scripts/docker-php.sh create-db          # Create database
./scripts/docker-php.sh migrate            # Run migrations
./scripts/docker-php.sh console doctrine:migrations:status

# Metrics Collection (NEW)
./scripts/docker-php.sh collect-metrics    # Collect real-time metrics
./scripts/docker-php.sh collect-metrics --dry-run  # Preview collection
./scripts/docker-php.sh generate-metrics   # Generate sample data
./scripts/docker-php.sh collect-metrics --cleanup-days=1  # With cleanup

# Development Tools
./scripts/docker-php.sh console cache:clear    # Clear cache
./scripts/docker-php.sh test                   # Run PHPUnit tests
./scripts/docker-php.sh console lint:container # Code quality check
```

### **Frontend Commands** (React/TypeScript)
```bash
# Package Management
./scripts/docker-node.sh install           # Install npm dependencies
./scripts/docker-node.sh add <package>     # Add dependency
./scripts/docker-node.sh add-dev <package> # Add dev dependency
./scripts/docker-node.sh remove <package>  # Remove package

# Development
./scripts/docker-node.sh dev               # Start development server
./scripts/docker-node.sh build             # Production build
./scripts/docker-node.sh lint              # ESLint check
./scripts/docker-node.sh clean             # Clean node_modules
```

## 📊 **API Endpoints & Features**

### **Container Management API**
```bash
# Container operations
GET  /api/docker/containers           # List all containers
POST /api/docker/containers/{id}/start    # Start container
POST /api/docker/containers/{id}/stop     # Stop container
POST /api/docker/containers/{id}/restart  # Restart container

# Test endpoints
curl http://localhost:80/api/docker/containers
curl -X POST http://localhost:80/api/docker/containers/{id}/start
```

### **Infrastructure Metrics API (NEW)**
```bash
# Metrics endpoints
GET /api/infrastructure/metrics                    # Get all metrics
GET /api/infrastructure/metrics/latest             # Latest metrics only
GET /api/infrastructure/metrics/chart/{source}/{metric}?hours=1  # Chart data

# Chart data examples
curl "http://localhost:80/api/infrastructure/metrics/chart/docker/cpu_percent?hours=1"
curl "http://localhost:80/api/infrastructure/metrics/chart/docker/memory_percent?hours=1"
```

### **Authentication API**
```bash
# Auth endpoints
POST /api/auth/login     # JWT login
POST /api/auth/logout    # Logout and invalidate token
```

## 🔄 **Real-time Features**

### **Dashboard Auto-refresh**
- **Charts**: Update every 30 seconds with new data
- **Container List**: Refreshes every 5 seconds
- **Dynamic Intervals**: 5-minute aggregation for 1-hour charts

### **Interactive Features**
- **Container Actions**: Start/stop/restart with visual feedback
- **Loading States**: Spinners and skeleton components
- **Success Feedback**: Green indicators for successful actions
- **Error Handling**: Graceful fallback to mock data if API unavailable

### **Chart Specifications**
- **CPU Chart**: Line chart with blue gradient, shows last 1 hour
- **Memory Chart**: Area chart with purple gradient, 5-minute intervals
- **Timezone**: Automatic UTC to local time conversion
- **Responsive**: Mobile-friendly with touch interactions 