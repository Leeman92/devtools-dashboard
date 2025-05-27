# Multi-stage build for React frontend
FROM node:22-slim AS builder

WORKDIR /app

# Copy package files first for better layer caching
COPY package.json package-lock.json ./

# Install dependencies with proper handling for Alpine Linux
RUN npm ci --include=optional

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Production stage with nginx
FROM nginx:alpine AS production

# Copy built assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx configurations
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY .docker/nginx-main.conf /etc/nginx/nginx.conf

# Create nginx user and set permissions
RUN addgroup -g 1000 frontend && \
    adduser -D -u 1000 -G frontend frontend && \
    chown -R frontend:frontend /usr/share/nginx/html && \
    chown -R frontend:frontend /var/cache/nginx && \
    chown -R frontend:frontend /var/log/nginx && \
    chown -R frontend:frontend /etc/nginx/conf.d && \
    chown -R frontend:frontend /var/run && \
    mkdir -p /tmp/nginx && \
    chown -R frontend:frontend /tmp/nginx

# Switch to non-root user
USER frontend

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:80/ || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"] 