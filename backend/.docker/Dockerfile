# Use the pre-built dependencies base image from Harbor
FROM harbor.patricklehmann.dev/dashboard/backend-deps:latest AS base

# Re-declare ARGs for this stage
ARG USERNAME=backend
ARG UID=1000
ARG GID=1000
ARG DOCKER_GID=999

USER root

# Copy application source code (excluding dependencies which are already installed)
COPY --chown=$USERNAME:$USERNAME . .

# Complete composer installation with application code
RUN composer dump-autoload --optimize

# Copy Caddy configuration
COPY .docker/caddy/Caddyfile /etc/caddy/Caddyfile

USER $USERNAME

# Development stage - adds dev dependencies if needed
FROM base AS development

USER root

# Install dev dependencies only if needed
RUN composer install --optimize-autoloader

USER $USERNAME

# Production stage - already optimized
FROM base AS production

# Re-declare ARGs for this stage
ARG USERNAME=backend
ARG UID=1000
ARG GID=1000
ARG DOCKER_GID=999

# Production build is already optimized in base stage
# Just ensure proper permissions
USER root
RUN chown -R $USERNAME:$USERNAME /app
USER $USERNAME

# This Dockerfile is much faster because:
# 1. Dependencies are pre-installed in base image
# 2. Only application code needs to be copied
# 3. Minimal composer operations needed
# 4. Base image is cached in Harbor registry 