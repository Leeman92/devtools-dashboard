# Security Endpoints Configuration

## Overview

This document explains the security configuration for different endpoint types in the DevTools Dashboard application.

## Public Endpoints (No Authentication Required)

### Authentication Endpoints
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration

### Health Check
- `GET /health` - Basic application health status

## Protected Endpoints (Authentication Required)

### API Endpoints
- `GET /api/auth/me` - Get current user information
- `POST /api/auth/logout` - User logout
- `GET /api/dashboard/*` - Dashboard data and metrics
- `GET /api/infrastructure/*` - Infrastructure monitoring
- All other `/api/*` endpoints require `ROLE_USER`

## Test/Debug Endpoints (Environment-Specific)

### Development Environment Only
Test endpoints are only accessible in development and test environments:

- `GET /api/test/env` - Environment configuration
- `GET /api/test/logging` - Logging system test
- `GET /api/test/jwt-test` - JWT configuration test
- `GET /api/test/database` - Database connectivity test
- `POST /api/test/login-debug` - Login debugging
- `GET /api/test/error` - Error handling test
- `GET /api/test/500` - 500 error test
- `POST /api/test/auth-test` - Authentication logging test

### Production Environment
In production, test endpoints are **protected** and require authentication with `ROLE_USER`. This prevents:

- Information disclosure
- Unauthorized system probing
- Resource consumption attacks
- Exposure of debug information

## Security Configuration

### Firewall Rules

```yaml
firewalls:
    # Public authentication
    auth:
        pattern: ^/api/auth/(login|register)
        security: false
    
    # Public health check
    health:
        pattern: ^/health
        security: false
    
    # Protected API (requires JWT)
    api:
        pattern: ^/api
        jwt: ~
```

### Access Control

```yaml
access_control:
    # Public endpoints
    - { path: ^/api/auth/(login|register), roles: PUBLIC_ACCESS }
    - { path: ^/health, roles: PUBLIC_ACCESS }
    
    # Protected endpoints
    - { path: ^/api/auth, roles: ROLE_USER }
    - { path: ^/api, roles: ROLE_USER }

# Environment-specific overrides
when@dev:
    security:
        access_control:
            - { path: ^/api/test, roles: PUBLIC_ACCESS }

when@test:
    security:
        access_control:
            - { path: ^/api/test, roles: PUBLIC_ACCESS }
```

## Testing in Different Environments

### Development/Local Testing
```bash
# Test endpoints are publicly accessible
curl http://localhost:8000/api/test/env
curl http://localhost:8000/api/test/logging
```

### Production Testing
```bash
# Test endpoints require authentication
curl -H "Authorization: Bearer <jwt-token>" https://dashboard.patricklehmann.dev/api/test/env
```

### Health Check (Always Public)
```bash
# Available in all environments without authentication
curl https://dashboard.patricklehmann.dev/health
```

## Security Best Practices

1. **Never expose debug information** in production without authentication
2. **Use environment-specific configurations** for different access levels
3. **Monitor access logs** for unauthorized attempts to access test endpoints
4. **Regularly review** endpoint access patterns
5. **Keep test endpoints minimal** and remove unused ones

## Emergency Debug Access

If you need to debug production issues:

1. **Temporary Admin Access**: Create a temporary admin user with access to test endpoints
2. **Log Analysis**: Use the comprehensive logging system instead of test endpoints
3. **Monitoring Tools**: Use Prometheus/Grafana for system metrics
4. **Container Logs**: Access Docker container logs directly

## Monitoring and Alerting

Set up alerts for:
- Unauthorized access attempts to `/api/test/*` in production
- High error rates on authentication endpoints
- Unusual patterns in health check requests
- Failed JWT token validations

## Compliance Notes

This configuration helps maintain:
- **OWASP Security Guidelines** - Minimal attack surface
- **Principle of Least Privilege** - Environment-appropriate access
- **Defense in Depth** - Multiple security layers
- **Security by Design** - Secure defaults with explicit overrides 