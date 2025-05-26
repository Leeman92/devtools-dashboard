# DevTools Dashboard API

A comprehensive Symfony-based API for monitoring Docker services, CI/CD pipelines, and infrastructure metrics.

## 🚀 Features

- **Docker Monitoring**: Real-time monitoring of Docker Swarm services and containers
- **CI/CD Pipeline Tracking**: GitHub Actions workflow monitoring and historical data
- **Infrastructure Metrics**: Prometheus/Grafana integration for system metrics
- **Historical Data**: Time-series data storage for trend analysis
- **RESTful API**: Pure JSON API for frontend consumption
- **Real-time Logs**: Container and service log streaming
- **Health Monitoring**: System health checks and alerting

## 📋 Requirements

- PHP 8.2+
- MariaDB 10.11+
- Docker with Swarm mode
- GitHub Personal Access Token
- Composer

## 🛠️ Installation

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Configure environment variables**:
   ```bash
   # Database
   DATABASE_URL="mysql://user:password@localhost:3306/dashboard"
   
   # Docker
   DOCKER_SOCKET_PATH="/var/run/docker.sock"
   
   # GitHub
   GITHUB_TOKEN="your_github_token"
   GITHUB_API_URL="https://api.github.com"
   
   # Infrastructure (optional)
   PROMETHEUS_URL="http://localhost:9090"
   GRAFANA_URL="http://localhost:3000"
   ```

3. **Create database and run migrations**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Start collecting metrics**:
   ```bash
   # Collect all metrics
   php bin/console app:collect-metrics
   
   # Collect specific source
   php bin/console app:collect-metrics --source=docker
   php bin/console app:collect-metrics --source=github --repository=owner/repo
   ```

## 📊 API Endpoints

### Dashboard Overview

- `GET /` - Welcome message and API info
- `GET /health` - Health check endpoint
- `GET /api/dashboard` - Dashboard overview

### Docker Monitoring

- `GET /api/docker/services` - List all Docker Swarm services
- `GET /api/docker/containers` - List all Docker containers
- `GET /api/docker/services/{serviceId}/logs?lines=100` - Get service logs
- `GET /api/docker/containers/{containerId}/logs?lines=100` - Get container logs
- `GET /api/docker/services/{serviceName}/history?hours=24` - Service historical data

### GitHub CI/CD

- `GET /api/github/{owner}/{repo}` - Repository information
- `GET /api/github/{owner}/{repo}/workflows` - List workflows
- `GET /api/github/{owner}/{repo}/runs?limit=10` - Recent workflow runs
- `GET /api/github/{owner}/{repo}/stats?days=7` - Pipeline statistics
- `GET /api/github/{owner}/{repo}/history?hours=24` - Pipeline historical data

### Infrastructure Metrics

- `GET /api/infrastructure/metrics?source=prometheus&metric=cpu_usage&hours=1` - Get metrics
- `GET /api/infrastructure/metrics/latest` - Latest metrics for all sources
- `GET /api/infrastructure/metrics/summary?hours=24` - Metrics summary
- `GET /api/infrastructure/metrics/sources` - Available metric sources
- `GET /api/infrastructure/metrics/names?source=prometheus` - Available metric names
- `GET /api/infrastructure/health` - Infrastructure health status
- `GET /api/infrastructure/metrics/chart/{source}/{metricName}?hours=24` - Chart data

## 🔧 Configuration

### Service Configuration

The application uses Symfony's service container for dependency injection. Key services:

- `DockerService`: Docker API integration
- `GitHubService`: GitHub API integration
- `InfrastructureMetric`: Metrics storage and retrieval

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DATABASE_URL` | MariaDB connection string | Required |
| `DOCKER_SOCKET_PATH` | Docker socket path | `/var/run/docker.sock` |
| `GITHUB_TOKEN` | GitHub Personal Access Token | Required |
| `GITHUB_API_URL` | GitHub API URL | `https://api.github.com` |
| `PROMETHEUS_URL` | Prometheus server URL | Optional |
| `GRAFANA_URL` | Grafana server URL | Optional |

### Docker Socket Access

The application needs access to the Docker socket to monitor services and containers:

```yaml
# docker-stack.yml
volumes:
  - /var/run/docker.sock:/var/run/docker.sock:ro
```

## 📈 Data Collection

### Automated Collection

Set up a cron job to collect metrics regularly:

```bash
# Collect metrics every 5 minutes
*/5 * * * * /usr/local/bin/php /app/bin/console app:collect-metrics --source=docker

# Collect GitHub metrics every 15 minutes
*/15 * * * * /usr/local/bin/php /app/bin/console app:collect-metrics --source=github --repository=owner/repo
```

### Manual Collection

```bash
# Collect all metrics
php bin/console app:collect-metrics

# Dry run (no data storage)
php bin/console app:collect-metrics --dry-run

# Specific repository
php bin/console app:collect-metrics --repository=patricklehmann/devtools-dashboard
```

## 🏗️ Architecture

### Entities

- **DockerService**: Docker Swarm service tracking
- **CicdPipeline**: GitHub Actions workflow runs
- **InfrastructureMetric**: System metrics and alerts

### Services

- **DockerService**: Docker API integration and data parsing
- **GitHubService**: GitHub API integration and webhook handling
- **MetricsCollector**: Automated data collection

### Controllers

- **DashboardController**: Main API endpoints
- **InfrastructureController**: Infrastructure metrics endpoints

## 🔍 Monitoring & Alerting

### Health Checks

The API provides health check endpoints:

- `/health` - Basic application health
- `/api/infrastructure/health` - Infrastructure health with alerts

### Alert Levels

- **Normal**: All systems operational
- **Warning**: Some metrics above threshold (80-95%)
- **Critical**: Metrics above critical threshold (95%+)

### Metrics Storage

Historical data is stored with:
- Time-series data for trending
- Configurable retention periods
- Efficient indexing for queries
- Alert threshold tracking

## 🧪 Testing

```bash
# Run tests
php bin/phpunit

# Test specific functionality
php bin/console app:collect-metrics --dry-run
```

## 📝 API Response Format

All API endpoints return JSON in this format:

```json
{
  "data": {},
  "count": 0,
  "timestamp": "2024-01-26T10:30:00+00:00",
  "filters": {},
  "metadata": {}
}
```

### Error Responses

```json
{
  "error": {
    "message": "Error description",
    "code": 400,
    "details": {}
  },
  "timestamp": "2024-01-26T10:30:00+00:00"
}
```

## 🔐 Security

- Environment variables for sensitive data
- Docker socket read-only access
- GitHub token with minimal required permissions
- Input validation and sanitization
- Structured logging for audit trails

## 📚 Development

### Adding New Metrics

1. Create entity in `src/Entity/`
2. Add service in `src/Service/`
3. Create controller endpoints
4. Update collection command
5. Add tests

### Database Migrations

```bash
# Generate migration
php bin/console doctrine:migrations:diff

# Run migrations
php bin/console doctrine:migrations:migrate
```

## 🤝 Contributing

1. Follow PSR-12 coding standards
2. Add comprehensive tests
3. Update documentation
4. Use semantic commit messages

## 📄 License

Proprietary - Patrick Lehmann DevTools Dashboard 