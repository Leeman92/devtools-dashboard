# Backend Architecture

This document outlines the backend architecture of the DevTools Dashboard, focusing on the modular controller design and API organization.

## Overview

The backend is built with **Symfony 7.1** following modern PHP best practices with strict typing, dependency injection, and single responsibility principles. The architecture emphasizes modular design with focused controllers handling specific domains.

## Architecture Principles

### ✅ **Single Responsibility Principle**
Each controller handles one specific domain area (Docker, GitHub, Dashboard, etc.)

### ✅ **Separation of Concerns**
- **Controllers**: Handle HTTP requests/responses and routing
- **Services**: Contain business logic and external API integration
- **Entities**: Represent data models and database structures

### ✅ **Dependency Injection**
All services and dependencies are injected through Symfony's container

### ✅ **Strict Typing**
All PHP code uses `declare(strict_types=1);` and proper type hints

## Controller Structure

```
backend/src/Controller/
├── DashboardController.php      # Dashboard/general application routes
├── DockerController.php         # Docker container and service management
├── GitHubController.php         # GitHub API integration and CI/CD
├── AuthController.php           # Authentication and user management
├── InfrastructureController.php # Infrastructure monitoring and health
└── TestController.php           # Testing and development utilities
```

## Controller Responsibilities

### **DashboardController.php** (68 lines)
**Domain**: General application routes and dashboard information

**Routes**:
- `GET /` - Application home/welcome
- `GET /health` - Health check endpoint
- `GET /api/dashboard` - Dashboard metadata and features

**Dependencies**: LoggerInterface only

### **DockerController.php** (146 lines)
**Domain**: Docker container and service management

**Route Prefix**: `/api/docker`

**Routes**:
- `GET /services` - List Docker Swarm services
- `GET /containers` - List Docker containers
- `GET /services/{serviceId}/logs` - Get service logs
- `GET /containers/{containerId}/logs` - Get container logs
- `POST /containers/{containerId}/start` - Start container
- `POST /containers/{containerId}/stop` - Stop container
- `POST /containers/{containerId}/restart` - Restart container
- `GET /services/{serviceName}/history` - Get service history

**Dependencies**: DockerService, LoggerInterface

### **GitHubController.php** (91 lines)
**Domain**: GitHub API integration and CI/CD pipeline management

**Route Prefix**: `/api/github`

**Routes**:
- `GET /{owner}/{repo}/workflows` - List GitHub workflows
- `GET /{owner}/{repo}/runs` - List workflow runs
- `GET /{owner}/{repo}` - Get repository information
- `GET /{owner}/{repo}/stats` - Get pipeline statistics
- `GET /{owner}/{repo}/history` - Get pipeline history

**Dependencies**: GitHubService, LoggerInterface

### **AuthController.php** (257 lines)
**Domain**: Authentication and user management

**Route Prefix**: `/api/auth`

**Features**: JWT authentication, login/logout, user management

### **InfrastructureController.php** (275 lines)
**Domain**: Infrastructure monitoring and system health

**Features**: System metrics, monitoring endpoints, infrastructure status

## Service Layer

### **DockerService.php** (509 lines)
**Purpose**: Docker API integration and container management

**Key Methods**:
- `getContainers()` - Fetch container list from Docker API
- `getSwarmServices()` - Fetch Swarm services
- `startContainer()` - Start Docker container
- `stopContainer()` - Stop Docker container
- `restartContainer()` - Restart Docker container
- `getContainerLogs()` - Retrieve container logs
- `getServiceLogs()` - Retrieve service logs

**Features**:
- Unix socket communication with Docker daemon
- Comprehensive error handling and logging
- Historical data storage for services
- Proper Docker API compliance (v1.24+)

### **GitHubService.php** (331 lines)
**Purpose**: GitHub API integration for CI/CD monitoring

**Key Methods**:
- `getWorkflows()` - Fetch GitHub workflows
- `getWorkflowRuns()` - Fetch workflow run history
- `getRepository()` - Get repository information
- `getPipelineStats()` - Calculate pipeline statistics
- `getPipelineHistory()` - Retrieve pipeline history

## API Design Patterns

### **Consistent Response Format**
All API endpoints return consistent JSON structure:

```json
{
  "data": {...},
  "count": 5,
  "timestamp": "2025-01-29T20:00:00+00:00"
}
```

### **Error Handling**
- Structured error responses with meaningful messages
- Proper HTTP status codes (200, 400, 404, 500)
- Comprehensive logging for debugging

### **Route Naming Convention**
- Route names follow pattern: `api_{domain}_{action}`
- Examples: `api_docker_containers`, `api_github_workflows`

## Development Workflow

### **Adding New Controllers**

1. **Create Controller Class**:
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/domain')]
final class DomainController extends AbstractController
{
    // Implementation
}
```

2. **Add Route Methods**:
```php
#[Route('/endpoint', name: 'api_domain_endpoint', methods: ['GET'])]
public function getEndpoint(): JsonResponse
{
    return $this->json(['data' => $data]);
}
```

3. **Add Service Dependencies**:
```php
public function __construct(
    private readonly DomainService $domainService,
    private readonly LoggerInterface $logger,
) {}
```

### **Testing Controllers**

```bash
# Test specific endpoints
curl http://localhost:80/api/docker/containers
curl http://localhost:80/api/github/owner/repo/workflows

# Run backend tests
./scripts/docker-php.sh test
```

## Security Considerations

### **Input Validation**
- All route parameters are validated and typed
- Request data is sanitized through Symfony validators
- SQL injection prevention through Doctrine ORM

### **Authentication**
- JWT token-based authentication
- Route-level access control
- User role and permission management

### **API Security**
- CORS headers properly configured
- Rate limiting implemented
- Request/response logging for audit trails

## Performance Optimization

### **Docker API Efficiency**
- Unix socket communication for minimal overhead
- Connection pooling and reuse
- Efficient cURL configuration with timeouts

### **Database Optimization**
- Doctrine ORM with query optimization
- Proper indexing on frequently queried fields
- Connection pooling for high concurrency

### **Caching Strategy**
- API response caching for frequently accessed data
- Redis integration for session and cache storage
- HTTP cache headers for client-side caching

## Monitoring and Logging

### **Structured Logging**
All controllers use structured logging with context:

```php
$this->logger->info('Container action requested', [
    'container_id' => $containerId,
    'action' => 'start',
    'user_id' => $user->getId(),
]);
```

### **Health Checks**
- Comprehensive health check endpoint at `/health`
- Service-specific health indicators
- Integration with monitoring systems

### **Metrics Collection**
- Request/response time tracking
- Error rate monitoring
- API usage statistics

## Future Architecture Considerations

### **Microservices Transition**
The current modular controller design facilitates future migration to microservices:
- Each controller could become a separate service
- Service layer already abstracts business logic
- API contracts are well-defined

### **Event-Driven Architecture**
- Domain events for cross-service communication
- Message queue integration for async processing
- Event sourcing for audit trails

### **API Versioning**
- Route prefixing for version management
- Backward compatibility strategies
- Deprecation policies for old endpoints

## Development Tools

### **Code Quality**
```bash
# PHP syntax validation
./scripts/docker-php.sh validate

# Code formatting
./scripts/docker-php.sh console lint:container

# Static analysis
./scripts/docker-php.sh console phpstan
```

### **Testing**
```bash
# Unit tests
./scripts/docker-php.sh test --testsuite=unit

# Integration tests
./scripts/docker-php.sh test --testsuite=integration

# API tests
./scripts/docker-php.sh test --testsuite=api
```

### **Documentation**
```bash
# Generate API documentation
./scripts/docker-php.sh console api:doc:export

# Update route cache
./scripts/docker-php.sh console cache:clear
```

This architecture provides a solid foundation for maintaining and scaling the DevTools Dashboard backend while following modern PHP and Symfony best practices. 