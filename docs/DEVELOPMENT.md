# Development Guide

This guide covers the development workflow, coding standards, and best practices for the DevTools Dashboard project.

## ✅ **Current Status: FULLY WORKING APPLICATION**

This is a complete, operational full-stack application with:
- **Real-time Docker container monitoring** with live status updates
- **Beautiful React + TypeScript frontend** with Tailwind CSS
- **JWT-based authentication system** with login/logout
- **Docker-first development environment** with hot reload
- **Production-ready deployment** with Docker Swarm

## Table of Contents

- [Development Environment Setup](#development-environment-setup)
- [Project Architecture](#project-architecture)
- [Coding Standards](#coding-standards)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Security Guidelines](#security-guidelines)
- [Performance Considerations](#performance-considerations)
- [Troubleshooting](#troubleshooting)

## Development Environment Setup

### Prerequisites

- Docker and Docker Compose
- Git

**Note**: No local PHP, Node.js, or npm installation required! All development operations use Docker containers.

### Quick Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd devtools-dashboard
```

2. Start the full development environment:
```bash
# Start both backend and frontend (recommended)
./scripts/dev.sh

# Or start services individually:
docker compose up -d                    # Backend only
./scripts/docker-node.sh dev           # Frontend only
```

3. Access the application:
- **Frontend Dashboard**: http://localhost:5173 (Beautiful React interface)
- **Backend API**: http://localhost:80 (Symfony API with Docker integration)
- **Database**: MariaDB on localhost:3306

### What You'll See

- **Modern Dashboard**: Beautiful gradient cards showing container status
- **Real-time Updates**: Live container monitoring with 5-second refresh
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Authentication**: Login/logout functionality with JWT tokens
- **Navigation**: Sidebar with Dashboard, Containers, CI/CD, Repositories tabs

### Available Make Commands

```bash
# Environment management
make up              # Start all services
make down            # Stop all services
make logs            # View logs
make build           # Build backend container

# Development
make install         # Install all dependencies
make login-backend   # Access backend container shell

# Testing
make test            # Run all tests
make test-verbose    # Run tests with verbose output
make test-coverage   # Generate coverage report
make test-help       # Show all test commands
```

## Project Architecture

### Backend (Symfony 7.2)

```
backend/
├── .docker/         # Docker configuration
├── bin/             # Symfony console and executables
├── config/          # Symfony configuration files
├── public/          # Web root (index.php)
├── src/             # Application source code
│   ├── Controller/  # HTTP controllers
│   ├── Entity/      # Doctrine entities
│   ├── Repository/  # Data repositories
│   ├── Service/     # Business logic services
│   └── ...
├── tests/           # Test suites
├── var/             # Cache, logs, sessions
└── vendor/          # Composer dependencies
```

### Infrastructure

- **Docker**: Containerized development and production environments
- **Docker Swarm**: Production orchestration
- **GitHub Actions**: CI/CD pipeline
- **HashiCorp Vault**: Secrets management
- **Nginx**: Reverse proxy and load balancing

## Coding Standards

### PHP Standards

All PHP code must follow these standards:

1. **Strict Types**: Always declare strict types
```php
<?php

declare(strict_types=1);
```

2. **PSR-12**: Follow PSR-12 coding standards
3. **Type Declarations**: Use type hints for all parameters and return types
4. **Final Classes**: Use `final` for classes that shouldn't be extended
5. **Readonly Properties**: Use `readonly` for immutable properties

### Example Controller

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dashboard controller for handling main application routes.
 */
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly SomeService $service,
    ) {}

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->service->getDashboardData();
            
            return $this->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Dashboard data retrieval failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
            ], 500);
        }
    }
}
```

### Service Example

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Service for handling dashboard business logic.
 */
final readonly class DashboardService
{
    public function __construct(
        private LoggerInterface $logger,
        private SomeRepository $repository,
    ) {}

    /**
     * Retrieve dashboard data with proper error handling.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException When data retrieval fails
     */
    public function getDashboardData(): array
    {
        try {
            return $this->repository->findDashboardData();
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve dashboard data', [
                'exception' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Dashboard data unavailable', 0, $e);
        }
    }
}
```

## Development Workflow

### 1. Feature Development

1. Create a feature branch:
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes following the coding standards
3. Write tests for your changes
4. Run tests locally:
```bash
make test
```

5. Commit your changes with descriptive messages:
```bash
git add .
git commit -m "feat: add dashboard data retrieval service

- Implement DashboardService for data aggregation
- Add proper error handling and logging
- Include comprehensive unit tests
- Follow Symfony best practices"
```

### 2. Commit Message Format

Follow conventional commits:
- `feat:` - New features
- `fix:` - Bug fixes
- `docs:` - Documentation changes
- `style:` - Code style changes
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

### 3. Pull Request Process

1. Push your branch to GitHub
2. Create a pull request with:
   - Clear description of changes
   - Link to related issues
   - Screenshots for UI changes
3. Ensure all CI checks pass
4. Request code review
5. Address feedback and update PR

## Testing

### Test Structure

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\DashboardService;
use Psr\Log\LoggerInterface;

final class DashboardServiceTest extends TestCase
{
    private DashboardService $service;
    private LoggerInterface $logger;
    private SomeRepository $repository;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(SomeRepository::class);
        $this->service = new DashboardService($this->logger, $this->repository);
    }

    public function testGetDashboardDataReturnsExpectedData(): void
    {
        // Arrange
        $expectedData = ['key' => 'value'];
        $this->repository
            ->expects($this->once())
            ->method('findDashboardData')
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getDashboardData();

        // Assert
        $this->assertSame($expectedData, $result);
    }

    public function testGetDashboardDataThrowsExceptionOnRepositoryFailure(): void
    {
        // Arrange
        $this->repository
            ->expects($this->once())
            ->method('findDashboardData')
            ->willThrowException(new \Exception('Database error'));

        $this->logger
            ->expects($this->once())
            ->method('error');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dashboard data unavailable');
        
        $this->service->getDashboardData();
    }
}
```

### Running Tests

```bash
# Run all tests
make test

# Run with verbose output
make test-verbose

# Generate coverage report
make test-coverage

# Run specific test file
make test-file TEST_FILE=tests/Unit/Service/DashboardServiceTest.php

# Run tests matching a filter
make test-filter FILTER=testGetDashboardData
```

## Security Guidelines

### 1. Secrets Management

- **Never commit secrets** to version control
- Use HashiCorp Vault for all sensitive data
- Use environment variables for configuration
- Rotate secrets regularly

### 2. Input Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;
}
```

### 3. Output Escaping

Always escape output and use Symfony's built-in security features:

```php
// In controllers
return $this->json($data); // Automatically escapes JSON

// In templates (if using Twig)
{{ user.name|e }} {# Escapes HTML #}
```

### 4. Authentication & Authorization

```php
#[Route('/api/admin', name: 'admin_dashboard')]
#[IsGranted('ROLE_ADMIN')]
public function adminDashboard(): JsonResponse
{
    // Only accessible to users with ROLE_ADMIN
}
```

## Performance Considerations

### 1. Database Optimization

- Use proper indexing
- Implement query optimization
- Use Doctrine query builder for complex queries
- Implement pagination for large datasets

### 2. Caching

```php
use Symfony\Contracts\Cache\CacheInterface;

final readonly class CachedDashboardService
{
    public function __construct(
        private CacheInterface $cache,
        private DashboardService $dashboardService,
    ) {}

    public function getDashboardData(): array
    {
        return $this->cache->get('dashboard_data', function () {
            return $this->dashboardService->getDashboardData();
        });
    }
}
```

### 3. Response Optimization

- Use appropriate HTTP status codes
- Implement proper HTTP caching headers
- Compress responses when appropriate
- Use pagination for large datasets

## Troubleshooting

### Common Issues

1. **Container won't start**:
```bash
# Check logs
make logs

# Rebuild containers
make down
make build
make up
```

2. **Permission issues**:
```bash
# Fix file permissions
sudo chown -R $USER:$USER .
```

3. **Database connection issues**:
```bash
# Check if database container is running
docker compose ps

# Check database logs
docker compose logs database
```

4. **Composer issues**:
```bash
# Clear composer cache
make login-backend
composer clear-cache
composer install
```

### Debug Mode

Enable debug mode in development:

```bash
# In backend/.env
APP_ENV=dev
APP_DEBUG=true
```

### Logging

Check application logs:

```bash
# View Symfony logs
tail -f backend/var/log/dev.log

# View container logs
make logs
```

## Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Docker Documentation](https://docs.docker.com/)
- [Project Rules](.cursorrules)
- [Vault Setup Guide](vault-setup.md) 