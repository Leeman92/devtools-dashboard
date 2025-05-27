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
COPY .docker/nginx-simple.conf /etc/nginx/conf.d/default.conf
# Use default nginx.conf (don't override main config)

# Just copy the files and use default nginx setup
# No custom user setup - use default nginx behavior

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://127.0.0.1:80/health || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"] 