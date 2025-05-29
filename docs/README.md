# DevTools Dashboard - Documentation Index

Welcome to the DevTools Dashboard documentation. This directory contains comprehensive documentation for all aspects of the project.

## 📋 Quick Links

| Document | Description | Status |
|----------|-------------|---------|
| [Development Guide](./DEVELOPMENT.md) | Complete development setup and workflow | ✅ Current |
| [Frontend Architecture](./FRONTEND_ARCHITECTURE.md) | React component structure and patterns | ✅ Current |
| [Deployment Guide](./DEPLOYMENT.md) | Production deployment with Docker Swarm | ✅ Current |
| [Security & Authentication](./SECURITY_ENDPOINTS.md) | JWT authentication and security practices | ✅ Current |
| [Logging Setup](./LOGGING_SETUP.md) | Centralized logging configuration | ✅ Current |
| [Docker Socket Access](./DOCKER_SOCKET_ACCESS.md) | Docker integration and troubleshooting | ✅ Current |
| [Vault Configuration](./vault-setup.md) | HashiCorp Vault secrets management | ✅ Current |
| [External MySQL Setup](./MYSQL_EXTERNAL_SETUP.md) | External database configuration | ✅ Current |
| [Project Status](./CURRENT_STATUS.md) | Current project status and features | ✅ Current |
| [TODO & Roadmap](./TODO.md) | Development roadmap and planned features | ✅ Current |
| [Quick Reference](./QUICK_REFERENCE.md) | Development commands and shortcuts | ✅ Current |

## 🏗️ Architecture Overview

### System Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React SPA     │    │   Symfony API   │    │   Docker Host   │
│   (Frontend)    │◄──►│   (Backend)     │◄──►│   (Containers)  │
│                 │    │                 │    │                 │
│ • TypeScript    │    │ • PHP 8.4+      │    │ • Docker Socket │
│ • Tailwind CSS  │    │ • JWT Auth      │    │ • Real-time API │
│ • Vite Build    │    │ • Doctrine ORM  │    │ • Monitoring    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │  Infrastructure │
                    │                 │
                    │ • Docker Swarm  │
                    │ • Nginx Proxy   │
                    │ • HashiCorp     │
                    │   Vault         │
                    │ • MySQL DB      │
                    └─────────────────┘
```

### Technology Stack

#### Frontend
- **React 18+** with TypeScript for type-safe development
- **Vite** for fast development and optimized builds
- **Tailwind CSS** with shadcn/ui components
- **Recharts** for real-time data visualization and charts
- **date-fns** for timezone-aware date formatting
- **JWT Authentication** with context providers
- **Real-time Updates** via 30-second polling (WebSocket planned)

#### Backend
- **Symfony 7** with PHP 8.4+ for modern API development
- **Doctrine ORM** for database operations and metrics storage
- **JWT Authentication** for stateless security
- **Docker API Integration** for container management
- **Metrics Collection System** with automated cleanup
- **Structured Logging** with Monolog

#### Infrastructure
- **Docker Swarm** for production orchestration
- **Nginx** for reverse proxy and SSL termination
- **HashiCorp Vault** for secrets management
- **MySQL 8** for data persistence
- **GitHub Actions** for CI/CD automation

## 📚 Documentation Structure

### Development Documentation
- **[DEVELOPMENT.md](./DEVELOPMENT.md)**: Complete development setup guide
  - Docker-first development workflow
  - Environment configuration
  - Database migrations
  - Testing procedures

- **[FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md)**: Frontend architecture details
  - Component structure and patterns
  - State management strategy
  - TypeScript type definitions
  - Development workflow

- **[BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)**: Backend architecture details
  - Controller organization and responsibilities
  - Service layer design patterns
  - API design and routing structure
  - Development standards and best practices

- **[DOCKER_SOCKET_ACCESS.md](./DOCKER_SOCKET_ACCESS.md)**: Docker integration guide
  - Docker socket configuration
  - Container API access
  - Troubleshooting procedures
  - Security considerations

### Deployment & Operations
- **[DEPLOYMENT.md](./DEPLOYMENT.md)**: Production deployment guide
  - Docker Swarm configuration
  - Environment setup
  - Monitoring and maintenance
  - Troubleshooting procedures

- **[LOGGING_SETUP.md](./LOGGING_SETUP.md)**: Centralized logging configuration
  - Log aggregation setup
  - Monitoring configuration
  - Log analysis workflows

### Security & Configuration
- **[SECURITY_ENDPOINTS.md](./SECURITY_ENDPOINTS.md)**: Security implementation
  - JWT authentication flow
  - API security patterns
  - Best practices

- **[vault-setup.md](./vault-setup.md)**: Vault configuration
  - Secrets management setup
  - Authentication configuration
  - Security policies

- **[vault-secrets-template.md](./vault-secrets-template.md)**: Vault secrets reference
  - Required secrets documentation
  - Environment-specific configurations

- **[MYSQL_EXTERNAL_SETUP.md](./MYSQL_EXTERNAL_SETUP.md)**: External database setup
  - Database configuration
  - Connection management
  - Migration procedures

### Project Management
- **[CURRENT_STATUS.md](./CURRENT_STATUS.md)**: Project status overview
  - Current features and capabilities
  - Recent changes and updates
  - System status indicators

- **[TODO.md](./TODO.md)**: Development roadmap
  - Planned features and enhancements
  - Development priorities
  - Future releases roadmap

- **[QUICK_REFERENCE.md](./QUICK_REFERENCE.md)**: Developer quick reference
  - Common commands and workflows
  - Development shortcuts
  - Troubleshooting quick fixes

## 🚀 Getting Started

### For Developers
1. **Setup Development Environment**
   ```bash
   # Clone and setup
   git clone <repository-url>
   cd devtools-dashboard
   
   # Validate setup
   ./scripts/validate-setup.sh
   
   # Start development
   ./scripts/dev.sh
   ```

2. **Read Key Documentation**
   - Start with [DEVELOPMENT.md](./DEVELOPMENT.md) for setup
   - Review [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md) for frontend work
   - Check [Security Endpoints](./SECURITY_ENDPOINTS.md) for API development

### For DevOps/Deployment
1. **Production Deployment**
   - Review [DEPLOYMENT.md](./DEPLOYMENT.md) for deployment procedures
   - Configure [Vault](./vault-setup.md) for secrets management
   - Setup [Logging](./LOGGING_SETUP.md) for monitoring

2. **Infrastructure Setup**
   - Configure external [MySQL](./MYSQL_EXTERNAL_SETUP.md) if needed
   - Setup monitoring and alerting
   - Configure backup procedures

## 🔄 Development Workflow

### Standard Development Process
```bash
# 1. Validate environment
./scripts/validate-setup.sh

# 2. Start development environment
./scripts/dev.sh

# 3. Make changes and test
./scripts/docker-node.sh build    # Frontend build test
./scripts/docker-php.sh validate  # Backend validation

# 4. Commit changes
git add .
git commit -m "feat: description of changes"

# 5. Deploy to staging/production
# Follow DEPLOYMENT.md procedures
```

### Code Quality Standards
- **TypeScript**: Strict mode for frontend
- **PHP**: Strict types and modern practices
- **Testing**: Comprehensive test coverage
- **Documentation**: Keep docs updated with changes
- **Security**: Follow security best practices

## 📊 Current Project Status

### ✅ Completed Features
- **Full-stack Authentication**: JWT-based login system
- **Docker Monitoring**: Real-time container status and management
- **Container Management**: Start/stop/restart actions with visual feedback
- **Real-time Charts**: CPU and Memory usage visualization with Recharts
- **Metrics Collection**: Automated data collection with configurable cleanup
- **Modern UI**: React TypeScript with Tailwind CSS and gradient designs
- **Production Deployment**: Docker Swarm with proper orchestration
- **Secrets Management**: HashiCorp Vault integration
- **Component Architecture**: Modular, maintainable frontend structure
- **Timezone Support**: Proper UTC to local time conversion

### 🚧 In Progress
- **WebSocket Integration**: Real-time updates without polling
- **Enhanced Container Features**: Log streaming and terminal access
- **Advanced Monitoring**: System-level metrics and performance analytics

### 📋 Next Priority Features
- **Container Logs**: Real-time log streaming with search and filtering
- **WebSocket Connection**: Eliminate polling for true real-time updates
- **CI/CD Integration**: GitHub Actions workflow monitoring

## 🤝 Contributing

### Documentation Updates
When making changes to the project:

1. **Update Relevant Documentation**: Keep docs in sync with code changes
2. **Follow Documentation Standards**: Use consistent formatting and structure
3. **Update This Index**: Add new documentation files to the index
4. **Review Documentation**: Ensure accuracy and completeness

### Documentation Guidelines
- Use **clear, descriptive headings**
- Include **code examples** where helpful
- Maintain **consistent formatting**
- Keep **table of contents** updated
- Use **proper Markdown syntax**

## 📞 Support & Resources

### Internal Resources
- **Development Issues**: Check [DEVELOPMENT.md](./DEVELOPMENT.md) troubleshooting
- **Deployment Problems**: Review [DEPLOYMENT.md](./DEPLOYMENT.md) troubleshooting
- **Security Questions**: Consult [SECURITY_ENDPOINTS.md](./SECURITY_ENDPOINTS.md)

### External Resources
- [Docker Documentation](https://docs.docker.com/)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [React Documentation](https://react.dev/)
- [HashiCorp Vault](https://developer.hashicorp.com/vault/docs)

---

**Note**: This documentation is actively maintained and updated with each release. Last updated: May 2025 