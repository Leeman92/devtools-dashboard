FROM dunglas/frankenphp:1.3.6-php8.4-alpine AS base

WORKDIR /app
ARG USERNAME=backend
ARG UID=1000
ARG GID=1000

# Create group and user with the same UID/GID as the host user
RUN addgroup -g $GID $USERNAME \
 && adduser -D -u $UID -G $USERNAME $USERNAME
 
COPY . .

COPY .docker/caddy/Caddyfile /etc/caddy/Caddyfile

RUN chown -R $USERNAME:$USERNAME .

USER $USERNAME


COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

FROM base AS development

USER root

RUN apk add --no-cache \
    bash

USER $USERNAME

RUN pwd && ls -ltr && composer install --optimize-autoloader

USER root

FROM base AS production

USER $USERNAME
RUN composer install -vvv
USER root