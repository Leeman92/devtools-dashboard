# HashiCorp Vault Secrets Template

This document shows what secrets should be stored in HashiCorp Vault for the dashboard application.

## Vault Path Structure

```
secret/dashboard/production    # Production secrets
secret/dashboard/staging       # Staging secrets  
secret/dashboard/development   # Development secrets
```

## Required Secrets

These secrets **must** be present in Vault for the application to function:

| Vault Key | Environment Variable | Description | Example |
|-----------|---------------------|-------------|---------|
| `app_secret` | `APP_SECRET` | Symfony application secret (32+ chars) | `a1b2c3d4e5f6...` |
| `database_url` | `DATABASE_URL` | Database connection string | `mysql://user:pass@host:3306/db` |

## Optional Secrets

These secrets are optional and the application will work without them:

| Vault Key | Environment Variable | Description | Example |
|-----------|---------------------|-------------|---------|
| `mailer_dsn` | `MAILER_DSN` | Email service configuration | `smtp://user:pass@smtp.example.com:587` |
| `redis_url` | `REDIS_URL` | Redis cache connection | `redis://localhost:6379` |
| `jwt_secret_key` | `JWT_SECRET_KEY` | JWT private key path | `/app/config/jwt/private.pem` |
| `jwt_public_key` | `JWT_PUBLIC_KEY` | JWT public key path | `/app/config/jwt/public.pem` |
| `jwt_passphrase` | `JWT_PASSPHRASE` | JWT key passphrase | `your-jwt-passphrase` |

## Vault Commands

### Store Production Secrets

```bash
# Store required secrets
vault kv put secret/dashboard/production \
  app_secret="your-32-character-secret-key-here" \
  database_url="mysql://username:password@host:3306/dashboard_prod"

# Store optional secrets
vault kv put secret/dashboard/production \
  mailer_dsn="smtp://user:pass@smtp.example.com:587" \
  redis_url="redis://localhost:6379"
```

### Store Staging Secrets

```bash
vault kv put secret/dashboard/staging \
  app_secret="staging-32-character-secret-key" \
  database_url="mysql://username:password@host:3306/dashboard_staging"
```

### View Stored Secrets

```bash
# List all secrets for production
vault kv get secret/dashboard/production

# Get specific secret
vault kv get -field=app_secret secret/dashboard/production
```

## Security Best Practices

1. **Rotate Secrets Regularly**: Update secrets periodically
2. **Use Strong Passwords**: Generate cryptographically secure secrets
3. **Limit Access**: Use Vault policies to restrict access
4. **Audit Access**: Monitor who accesses secrets
5. **Environment Separation**: Use different secrets for each environment

## Generating Secure Secrets

### APP_SECRET (Symfony)
```bash
# Generate a 32-character secret
openssl rand -hex 32
```

### Database Password
```bash
# Generate a strong database password
openssl rand -base64 32
```

### JWT Keys (if using JWT authentication)
```bash
# Generate JWT key pair
openssl genpkey -algorithm RSA -out private.pem -pkcs8
openssl rsa -pubout -in private.pem -out public.pem
``` 