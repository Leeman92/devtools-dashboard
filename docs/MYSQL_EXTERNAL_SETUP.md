# External MySQL Setup for DevTools Dashboard

This guide explains how to set up MySQL in a standalone Docker container and integrate it with your Docker Swarm deployment.

## 🎯 Overview

Instead of managing MySQL as part of the Docker Stack, this approach:
- ✅ Gives you full control over MySQL configuration and data
- ✅ Allows independent MySQL management and backups
- ✅ Simplifies stack deployments (no database state to manage)
- ✅ Enables easier database maintenance and upgrades
- ✅ Reduces Docker Stack complexity

## 📋 Prerequisites

- Docker installed and running
- Docker Swarm initialized (`docker swarm init`)
- SSH access to your server
- HashiCorp Vault configured (for storing credentials)

## 🚀 Step-by-Step Setup

### Step 1: Set Up MySQL Container

First, ensure your Vault environment is configured:

```bash
# Set your Vault environment variables
export VAULT_ADDR="https://your-vault-server.com"
export VAULT_TOKEN="your-vault-token"
```

Then run the setup script on your server:

```bash
# Make the script executable
chmod +x scripts/deployment/setup-standalone-mysql.sh

# Run the setup script (automatically integrates with Vault)
./scripts/deployment/setup-standalone-mysql.sh production
```

The script will:
1. **Generate secure passwords** (32 characters each)
2. **Store passwords in HashiCorp Vault** automatically
3. **Update DATABASE_URL** in Vault to point to dashboard-mysql
4. **Create data directory** (`/var/lib/mysql-dashboard`)
5. **Set up Docker network** (`dashboard-network`)
6. **Create MySQL container** with proper configuration
7. **Verify database setup** and user permissions
8. **Verify Vault integration** and secret storage

### Step 2: Verify MySQL Setup

After the script completes, verify everything is working:

```bash
# Check container status
docker ps --filter "name=dashboard-mysql"

# Test root connection
docker exec -it dashboard-mysql mysql -u root -p

# Test dashboard user connection
docker exec -it dashboard-mysql mysql -u dashboard -p

# Check network connectivity
docker network inspect dashboard-network
```

### Step 3: Verify Vault Integration

The setup script automatically stores all credentials in Vault. Verify they were stored correctly:

```bash
# Verify all secrets are stored
vault kv get secret/dashboard/production

# Check specific secrets
vault kv get -field=DATABASE_URL secret/dashboard/production
vault kv get -field=MYSQL_ROOT_PASSWORD secret/dashboard/production
vault kv get -field=MYSQL_DASHBOARD_PASSWORD secret/dashboard/production
```

The script automatically:
- ✅ **Generates secure 32-character passwords**
- ✅ **Stores all MySQL credentials in Vault**
- ✅ **Updates DATABASE_URL** to point to `dashboard-mysql:3306`
- ✅ **Preserves existing secrets** in your Vault path

### Step 4: Docker Stack Configuration

The main `docker-stack.yml` file has been updated to use external MySQL by default:

✅ **Already configured for external MySQL:**
- ✅ Database service removed (uses external `dashboard-mysql` container)
- ✅ Database secrets removed (credentials stored in Vault)
- ✅ Database volumes removed (data managed externally)
- ✅ Network set as external (uses existing `dashboard-network`)
- ✅ DATABASE_URL points to `dashboard-mysql:3306`

**No manual configuration needed** - the stack is ready to use your external MySQL!

### Step 5: Generate Environment Configuration

Generate the environment file with the new DATABASE_URL:

```bash
# This will pull the DATABASE_URL from Vault pointing to dashboard-mysql
./scripts/deployment/generate-env-file.sh
```

### Step 6: Deploy Your Application

Deploy the stack using the external MySQL:

```bash
# Deploy the stack
docker stack deploy -c docker-stack.yml dashboard

# Verify services are running
docker service ls

# Check that backend can connect to MySQL
docker service logs dashboard_dashboard-backend
```

### Step 7: Initialize Database Schema

After deployment, you need to create the database schema and initial user:

```bash
# Find the backend container name
docker ps | grep backend

# Run Doctrine migrations to create tables
docker exec <backend-container-name> php bin/console doctrine:migrations:migrate --no-interaction

# Create initial admin user
docker exec -it <backend-container-name> php bin/console app:create-user

# Verify tables were created
docker exec dashboard-mysql mysql -u dashboard -p -e "USE dashboard; SHOW TABLES;"
```

**Important**: The MySQL setup script only creates an empty database. The application schema (tables, indexes, etc.) is created by Symfony Doctrine migrations.

## 🔧 Configuration Details

### MySQL Container Configuration

The setup script creates a MySQL container with:

```bash
Container Name: dashboard-mysql
Network: dashboard-network
Database: dashboard
User: dashboard
Host: dashboard-mysql (internal hostname)
Port: 3306
Data Directory: /var/lib/mysql-dashboard
```

### Security Features

- **Secure passwords**: 32-character generated passwords
- **Isolated network**: Container runs on dedicated Docker network
- **Persistent data**: Data stored outside container in `/var/lib/mysql-dashboard`
- **Proper permissions**: MySQL user (999:999) owns data directory
- **No external ports**: MySQL only accessible within Docker network

### Performance Optimizations

The container includes MySQL performance tuning:

```bash
--character-set-server=utf8mb4
--collation-server=utf8mb4_unicode_ci
--innodb-buffer-pool-size=256M
--max-connections=100
--query-cache-size=32M
```

## 🌐 Docker Swarm Integration

### Network Configuration

The MySQL container connects to the same network as your Docker Stack:

```yaml
networks:
  dashboard-network:
    external: true  # Uses existing network created by MySQL setup
```

### Service Discovery

Your application services can connect to MySQL using the hostname:
- **Hostname**: `dashboard-mysql`
- **Port**: `3306`
- **Full connection**: `mysql://dashboard:password@dashboard-mysql:3306/dashboard`

### Stack Dependencies

Since MySQL runs as a standalone container:
- ✅ **Independent lifecycle**: MySQL can restart without affecting the stack
- ✅ **Simplified deployments**: Stack deployments don't manage database state
- ✅ **Better resource management**: MySQL resources managed separately

## 🛠️ Management Commands

### Daily Operations

```bash
# View MySQL logs
docker logs dashboard-mysql

# Connect to MySQL as root
docker exec -it dashboard-mysql mysql -u root -p

# Connect as dashboard user
docker exec -it dashboard-mysql mysql -u dashboard -p

# Check MySQL status
docker exec dashboard-mysql mysqladmin status -u root -p

# Show databases
docker exec dashboard-mysql mysql -u root -p -e "SHOW DATABASES;"
```

### Backup and Restore

```bash
# Create backup
docker exec dashboard-mysql mysqldump -u root -p dashboard > dashboard-backup-$(date +%Y%m%d).sql

# Restore from backup
docker exec -i dashboard-mysql mysql -u root -p dashboard < dashboard-backup.sql

# Backup with compression
docker exec dashboard-mysql mysqldump -u root -p dashboard | gzip > dashboard-backup-$(date +%Y%m%d).sql.gz
```

### Container Management

```bash
# Stop MySQL container
docker stop dashboard-mysql

# Start MySQL container
docker start dashboard-mysql

# Restart MySQL container
docker restart dashboard-mysql

# View container resource usage
docker stats dashboard-mysql

# Update MySQL (requires data migration planning)
# 1. Backup data
# 2. Stop old container
# 3. Run setup script with new version
# 4. Restore data if needed
```

## 🔍 Troubleshooting

### Connection Issues

```bash
# Test network connectivity from another container
docker run --rm --network dashboard-network alpine ping dashboard-mysql

# Check if MySQL is accepting connections
docker exec dashboard-mysql mysqladmin ping -h localhost -u root -p

# Verify network configuration
docker network inspect dashboard-network
```

### Performance Issues

```bash
# Check MySQL process list
docker exec dashboard-mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# View MySQL status
docker exec dashboard-mysql mysql -u root -p -e "SHOW STATUS;"

# Check slow queries
docker exec dashboard-mysql mysql -u root -p -e "SHOW VARIABLES LIKE 'slow_query_log';"
```

### Data Issues

```bash
# Check data directory permissions
ls -la /var/lib/mysql-dashboard/

# Verify database integrity
docker exec dashboard-mysql mysqlcheck -u root -p --all-databases

# Check table status
docker exec dashboard-mysql mysql -u root -p -e "USE dashboard; SHOW TABLE STATUS;"
```

## 🔒 Security Best Practices

### Password Management

1. **Store in Vault**: Never store passwords in plain text files
2. **Rotate regularly**: Change passwords quarterly
3. **Use strong passwords**: 32+ characters with high entropy
4. **Limit access**: Only application and admin users

### Network Security

1. **No external ports**: MySQL only accessible within Docker network
2. **Network isolation**: Use dedicated network for database traffic
3. **Monitor connections**: Regular audit of database connections

### Data Protection

1. **Regular backups**: Automated daily backups
2. **Encrypted storage**: Consider encrypting data directory
3. **Access logging**: Enable MySQL audit logging
4. **File permissions**: Proper ownership of data directory

## 📊 Monitoring and Maintenance

### Health Checks

```bash
# Add to monitoring script
#!/bin/bash
if docker exec dashboard-mysql mysqladmin ping -h localhost -u root -p"$ROOT_PASSWORD" --silent; then
    echo "MySQL is healthy"
else
    echo "MySQL is down"
    # Send alert
fi
```

### Log Management

```bash
# Rotate MySQL logs
docker exec dashboard-mysql mysql -u root -p -e "FLUSH LOGS;"

# View error log
docker exec dashboard-mysql tail -f /var/log/mysql/error.log
```

### Performance Monitoring

```bash
# Monitor connections
docker exec dashboard-mysql mysql -u root -p -e "SHOW STATUS LIKE 'Connections';"

# Monitor queries
docker exec dashboard-mysql mysql -u root -p -e "SHOW STATUS LIKE 'Questions';"

# Monitor buffer pool
docker exec dashboard-mysql mysql -u root -p -e "SHOW STATUS LIKE 'Innodb_buffer_pool%';"
```

## 🎯 Next Steps

After completing the MySQL setup:

1. **Test application connectivity** from your backend services
2. **Set up automated backups** using cron jobs
3. **Configure monitoring** for MySQL health and performance
4. **Plan maintenance windows** for MySQL updates
5. **Document recovery procedures** for disaster scenarios

## 📚 Additional Resources

- [MySQL Docker Official Documentation](https://hub.docker.com/_/mysql)
- [Docker Swarm Networking](https://docs.docker.com/network/overlay/)
- [HashiCorp Vault KV Secrets](https://www.vaultproject.io/docs/secrets/kv)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html) 