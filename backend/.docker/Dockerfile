FROM dunglas/frankenphp:1.3.6-php8.4-alpine AS base

WORKDIR /app
ARG USERNAME=backend
ARG UID=1000
ARG GID=1000

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    bash \
    && docker-php-ext-install \
    pdo \
    opcache 

# Create group and user with the same UID/GID as the host user
RUN addgroup -g $GID $USERNAME \
 && adduser -D -u $UID -G $USERNAME $USERNAME \
 && addgroup -g 962 docker \
 && adduser $USERNAME docker

# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Copy composer files first for better layer caching
COPY composer.json ./
COPY composer.lock ./
COPY symfony.lock ./

# Verify files were copied correctly
RUN ls -la composer.* symfony.lock

# Install dependencies as root first, then change ownership
RUN composer update --no-scripts && composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

COPY . .

# Fix ownership of all files
RUN chown -R $USERNAME:$USERNAME /app

# Complete composer installation
RUN composer dump-autoload --optimize

COPY .docker/caddy/Caddyfile /etc/caddy/Caddyfile

USER $USERNAME

FROM base AS development

USER root

RUN apk add --no-cache \
    bash

# Install dev dependencies
RUN composer update --no-scripts && composer install --optimize-autoloader

USER $USERNAME

FROM base AS production

# Re-declare ARGs for this stage
ARG USERNAME=backend
ARG UID=1000
ARG GID=1000

# Production is already optimized in base stage
# Just ensure proper permissions
USER root
RUN chown -R $USERNAME:$USERNAME /app
USER $USERNAME