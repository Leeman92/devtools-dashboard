# Production Logging Setup for DevTools Dashboard

## Overview

The DevTools Dashboard uses Symfony's Monolog bundle for comprehensive logging in production. This document explains the logging configuration, channels, and how to view and troubleshoot logs.

## Logging Configuration

### Environment Variables

The following environment variables control logging behavior:

```bash
# Log level for main application logs (debug, info, notice, warning, error, critical, alert, emergency)
LOG_LEVEL=info

# Default log level fallback
LOG_LEVEL_DEFAULT=info

# Log channel (stderr for Docker containers)
LOG_CHANNEL=stderr

# Enable JSON formatting for structured logs
MONOLOG_JSON_FORMAT=true
```

### Logging Channels

The application uses dedicated logging channels for different components:

1. **main** - General application logs
2. **docker** - Docker service operations and container monitoring
3. **github** - GitHub API interactions and CI/CD pipeline monitoring
4. **auth** - Authentication and authorization events
5. **metrics** - Metrics collection and processing
6. **deprecation** - Symfony deprecation warnings

### Production Logging Handlers

#### Main Application Handler
- **Type**: Stream
- **Output**: `php://stderr` (Docker container logs)
- **Level**: Configurable via `LOG_LEVEL` environment variable
- **Format**: JSON for structured logging
- **Channels**: All except event, doctrine, and deprecation

#### Channel-Specific Handlers
Each service has its own dedicated handler:
- **Docker Service**: Info level, JSON format
- **GitHub Service**: Info level, JSON format  
- **Auth Controller**: Info level, JSON format
- **Metrics Collection**: Info level, JSON format

#### Error Buffer Handler
- **Type**: Fingers Crossed
- **Purpose**: Captures context when errors occur
- **Trigger**: Error level events
- **Buffer Size**: 50 messages
- **Output**: `php://stderr` with debug level detail

## Viewing Logs

### Docker Container Logs

View logs from running containers:

```bash
# View backend service logs
docker service logs dashboard_dashboard-backend --follow --tail 100

# View frontend service logs  
docker service logs dashboard_dashboard-frontend --follow --tail 100

# View logs from specific container
docker logs <container-id> --follow --tail 100
```

### Structured Log Filtering

Since logs are in JSON format, you can filter them using `jq`:

```bash
# View only authentication logs
docker service logs dashboard_dashboard-backend --no-trunc | jq 'select(.channel == "auth")'

# View only error level logs
docker service logs dashboard_dashboard-backend --no-trunc | jq 'select(.level_name == "ERROR")'

# View logs for specific user
docker service logs dashboard_dashboard-backend --no-trunc | jq 'select(.context.user_id == "123")'

# View Docker service operations
docker service logs dashboard_dashboard-backend --no-trunc | jq 'select(.channel == "docker")'
```

### Log Aggregation

For production environments, consider setting up log aggregation:

1. **ELK Stack** (Elasticsearch, Logstash, Kibana)
2. **Grafana Loki** with Promtail
3. **Fluentd** with centralized storage
4. **Docker logging drivers** (syslog, journald, etc.)

## Log Levels and When They're Used

### DEBUG
- Detailed diagnostic information
- Only visible when `LOG_LEVEL=debug`
- Not recommended for production

### INFO
- General application flow
- Successful operations
- User actions (login, registration)
- Service interactions

### NOTICE  
- Normal but significant events
- Configuration changes
- Service status changes

### WARNING
- Potentially harmful situations
- Failed authentication attempts
- API rate limiting
- Deprecated feature usage

### ERROR
- Error conditions that don't stop the application
- Failed API calls
- Database connection issues
- Validation failures

### CRITICAL
- Critical conditions
- Application component failures
- Security violations

## Testing Logging

### Test Endpoints

Use the dedicated test endpoints to verify logging is working:

```bash
# Test all log levels
curl -X GET https://dashboard.patricklehmann.dev/api/test/logging

# Test environment configuration
curl -X GET https://dashboard.patricklehmann.dev/api/test/env

# Test error handling and logging
curl -X GET https://dashboard.patricklehmann.dev/api/test/error

# Test 500 error logging
curl -X GET https://dashboard.patricklehmann.dev/api/test/500

# Test authentication logging
curl -X POST https://dashboard.patricklehmann.dev/api/test/auth-test
```

### Health Check Logging

The health check endpoint also generates log entries:

```bash
curl -X GET https://dashboard.patricklehmann.dev/health
```

### Local Testing

Use the provided test script to verify logging locally:

```bash
./scripts/test-logging.sh
```

## Troubleshooting

### No Logs Appearing

1. **Check Log Level**: Ensure `LOG_LEVEL` is set appropriately
2. **Verify Configuration**: Check `config/packages/monolog.yaml`
3. **Container Status**: Ensure containers are running properly
4. **Docker Logs**: Check if Docker is capturing stderr output
5. **Test Endpoints**: Use `/api/test/logging` to verify basic logging works
6. **Exception Listener**: Verify the ExceptionListener is properly configured
7. **Environment Variables**: Check that all logging environment variables are set

### Log Level Not Working

1. **Environment Variables**: Verify `.env.production` contains correct values
2. **Cache Clear**: Clear Symfony cache after configuration changes
3. **Container Restart**: Restart containers to pick up new environment variables

### Missing Channel Logs

1. **Service Configuration**: Check `config/services.yaml` for logger injection
2. **Channel Registration**: Verify channels are registered in `monolog.yaml`
3. **Handler Configuration**: Ensure handlers are configured for specific channels

## Security Considerations

### Sensitive Data

- **Never log passwords** or other sensitive authentication data
- **Mask PII** (personally identifiable information) in logs
- **Sanitize user input** before logging
- **Use structured logging** to avoid log injection attacks

### Log Retention

- **Rotate logs** regularly to prevent disk space issues
- **Set retention policies** based on compliance requirements
- **Secure log storage** with appropriate access controls
- **Monitor log volume** to detect potential issues

## Performance Considerations

### Log Volume

- **Use appropriate log levels** for production
- **Avoid excessive debug logging** in high-traffic areas
- **Buffer logs** for better performance
- **Monitor log processing overhead**

### Structured Logging Benefits

- **Easier parsing** and analysis
- **Better filtering** capabilities
- **Improved monitoring** and alerting
- **Enhanced debugging** with context

## Integration with Monitoring

### Prometheus Metrics

Log-based metrics can be exported to Prometheus:

- Error rate by service
- Authentication failure rate
- API response times
- Service health status

### Grafana Dashboards

Create dashboards for:

- Application error rates
- Authentication events
- Service performance metrics
- Infrastructure health

### Alerting

Set up alerts for:

- High error rates
- Authentication failures
- Service unavailability
- Performance degradation

## Example Log Entries

### Authentication Success
```json
{
  "message": "User logged in successfully",
  "level": 200,
  "level_name": "INFO",
  "channel": "auth",
  "datetime": "2025-01-27T10:30:00+00:00",
  "context": {
    "user_id": 123,
    "email": "user@example.com",
    "ip": "192.168.1.100"
  }
}
```

### Docker Service Status
```json
{
  "message": "Retrieved Docker Swarm services",
  "level": 200,
  "level_name": "INFO", 
  "channel": "docker",
  "datetime": "2025-01-27T10:30:00+00:00",
  "context": {
    "service_count": 5
  }
}
```

### Application Error
```json
{
  "message": "Failed to retrieve GitHub repository",
  "level": 400,
  "level_name": "ERROR",
  "channel": "github", 
  "datetime": "2025-01-27T10:30:00+00:00",
  "context": {
    "repository": "owner/repo",
    "error": "API rate limit exceeded"
  }
}
```

## Best Practices

1. **Use structured logging** with consistent field names
2. **Include relevant context** in log messages
3. **Use appropriate log levels** for different events
4. **Avoid logging sensitive information**
5. **Monitor log volume** and performance impact
6. **Set up log rotation** and retention policies
7. **Use correlation IDs** for request tracing
8. **Test logging configuration** in staging environments
9. **Document log formats** and field meanings
10. **Regular log analysis** for security and performance insights 