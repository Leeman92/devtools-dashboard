# Docker Socket Access Guide

This guide explains how the DevTools Dashboard accesses the Docker socket for monitoring containers and services in both local development and production environments.

## 🔧 Configuration Overview

The application needs access to the Docker socket (`/var/run/docker.sock`) to:
- Monitor Docker Swarm services
- List and inspect containers
- Retrieve container logs
- Collect service metrics and health status

## 🏠 Local Development (Docker Compose)

### Configuration
The `docker-compose.yml` mounts the Docker socket as a read-only volume and sets the required environment variable:

```yaml
volumes:
  - ./backend:/app
  # Mount Docker socket for container monitoring
  - /var/run/docker.sock:/var/run/docker.sock:ro
environment:
  APP_ENV: dev
  DOCKER_SOCKET_PATH: /var/run/docker.sock
```

### Container User Permissions
The Dockerfile ensures the container user has access to the Docker group:

```dockerfile
# Create group and user with the same UID/GID as the host user
RUN addgroup -g $GID $USERNAME \
 && adduser -D -u $UID -G $USERNAME $USERNAME \
 && addgroup -g 962 docker \
 && adduser $USERNAME docker
```

### Environment Variable
```bash
DOCKER_SOCKET_PATH="/var/run/docker.sock"
```

### Testing Local Access
```bash
# Test Docker socket access from within the container
docker-compose exec backend ls -la /var/run/docker.sock

# Test Docker API access
docker-compose exec backend curl --unix-socket /var/run/docker.sock http://localhost/version
```

## 🚀 Production (Docker Swarm)

### Configuration
The `docker-stack.yml` includes:

1. **Socket Mount**: Read-only access to Docker socket
```yaml
volumes:
  - /var/run/docker.sock:/var/run/docker.sock:ro
```

2. **Placement Constraints**: Ensures containers run on manager nodes
```yaml
deploy:
  placement:
    constraints:
      - node.role == manager
```

### Why Manager Nodes?
- **Docker Swarm API**: Only manager nodes have full access to Swarm API
- **Service Information**: Worker nodes can't query service details
- **Security**: Limits socket access to trusted manager nodes

### Environment Variable
```bash
DOCKER_SOCKET_PATH="/var/run/docker.sock"
```

## 🔒 Security Considerations

### Read-Only Access
- Socket is mounted as `:ro` (read-only)
- Application can query but not modify Docker state
- Prevents accidental container manipulation

### Minimal Permissions
The application only needs:
- `GET /containers/json` - List containers
- `GET /services` - List Swarm services  
- `GET /containers/{id}/logs` - Read container logs
- `GET /services/{id}/logs` - Read service logs

### Network Isolation
- Containers run in isolated overlay network
- Socket access is limited to monitoring operations
- No privileged mode required

## 🧪 Testing Socket Access

### Local Development
```bash
# Start the application
docker-compose up -d

# Test API endpoints that use Docker socket
curl http://localhost/api/docker/services
curl http://localhost/api/docker/containers

# Check logs for Docker API errors
docker-compose logs backend
```

### Production Deployment
```bash
# Deploy the stack
docker stack deploy -c docker-stack.yml dashboard

# Test from within a container
docker exec -it $(docker ps -q -f label=com.docker.swarm.service.name=dashboard_dashboard) \
  curl --unix-socket /var/run/docker.sock http://localhost/version

# Test API endpoints
curl http://your-server:3001/api/docker/services
curl http://your-server:3001/api/docker/containers
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Permission Denied
```bash
# Error: dial unix /var/run/docker.sock: connect: permission denied
```
**Solution**: Ensure socket is mounted and container user has Docker group access (GID 962)

#### 2. Socket Not Found
```bash
# Error: dial unix /var/run/docker.sock: connect: no such file or directory
```
**Solution**: Verify socket mount in docker-compose.yml or docker-stack.yml

#### 3. HTTP Client Errors
```bash
# Error: Unsupported scheme in "unix://var/run/docker.sock": "http" or "https" expected
```
**Solution**: Use cURL with CURLOPT_UNIX_SOCKET_PATH instead of HTTP client

#### 4. API Errors in Swarm
```bash
# Error: This node is not a swarm manager
```
**Solution**: Ensure placement constraint `node.role == manager`

#### 5. Environment Variable Missing
```bash
# Error: DOCKER_SOCKET_PATH not set
```
**Solution**: Add DOCKER_SOCKET_PATH environment variable to docker-compose.yml

### Debugging Commands

```bash
# Check if socket is accessible
ls -la /var/run/docker.sock

# Check container user permissions
docker-compose exec backend id
docker-compose exec backend ls -la /var/run/docker.sock

# Test socket permissions
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock:ro \
  alpine/curl curl --unix-socket /var/run/docker.sock http://localhost/version

# Test Docker API directly from container
docker-compose exec backend curl --unix-socket /var/run/docker.sock 'http://localhost/containers/json?all=true'

# Check environment variables
docker-compose exec backend env | grep DOCKER

# Check container placement (Swarm)
docker service ps dashboard_dashboard

# Verify manager node constraint
docker node ls
```

## 📊 Monitoring Socket Usage

### Application Logs
The application logs Docker API calls:
```bash
# View Docker service logs
docker-compose logs backend | grep "Docker"

# Production logs
docker service logs dashboard_dashboard | grep "Docker"
```

### Health Checks
The application includes health checks for Docker connectivity:
```bash
# Test health endpoint
curl http://localhost/health
curl http://localhost/api/infrastructure/health
```

## 🔄 Alternative Approaches

### 1. Docker-in-Docker (DinD)
Not recommended for production due to security and complexity.

### 2. Remote Docker API
Configure Docker daemon to accept TCP connections:
```bash
# Not recommended for production
DOCKER_HOST="tcp://docker-host:2376"
```

### 3. Docker API Proxy
Use a dedicated service to proxy Docker API calls:
```yaml
services:
  docker-proxy:
    image: tecnativa/docker-socket-proxy
    environment:
      CONTAINERS: 1
      SERVICES: 1
      SWARM: 1
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
```

## 📝 Best Practices

1. **Always use read-only mounts** (`:ro`)
2. **Limit to manager nodes** in Swarm mode
3. **Monitor socket access** in application logs
4. **Use health checks** to verify connectivity
5. **Implement proper error handling** for socket failures
6. **Consider using Docker API proxy** for enhanced security
7. **Regular security audits** of socket access patterns

## 🔗 Related Documentation

- [Docker Socket Security](https://docs.docker.com/engine/security/)
- [Docker Swarm Services](https://docs.docker.com/engine/swarm/services/)
- [Docker API Reference](https://docs.docker.com/engine/api/)
- [Container Security Best Practices](https://docs.docker.com/engine/security/security/) 