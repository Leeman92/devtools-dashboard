# DevTools Dashboard - Quick Reference

## 🚀 Quick Start

```bash
# 1. Validate setup
./scripts/validate-setup.sh

# 2. Install dependencies (using Docker wrapper)
./scripts/docker-php.sh install

# 3. Configure environment
# Create backend/.env with your values (see documentation for required variables)

# 4. Setup database (using Docker wrapper)
./scripts/docker-php.sh create-db
./scripts/docker-php.sh migrate

# 5. Test the application (using Docker wrapper)
./scripts/docker-php.sh collect-metrics
```

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
| Socket access denied | Ensure Docker socket is mounted |

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
DOCKER_SOCKET_PATH="/var/run/docker.sock"
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

4. **Before deploying**:
   ```bash
   ./scripts/validate-setup.sh
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