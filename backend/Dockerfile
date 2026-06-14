# https://frankenphp.dev/docs/docker/

FROM dunglas/frankenphp:1.10.1-php8.5 AS base

RUN apt-get update && \
    apt-get install -y git unzip libzip-dev && \
    rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_pgsql gd intl zip opcache pcntl sodium exif


# Build stage
FROM base AS builder

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

FROM base AS production

WORKDIR /app

ARG USER=appuser

RUN useradd ${USER}

COPY --from=builder /app ./

COPY ./docker-entrypoint.sh /app/docker-entrypoint.sh
RUN chmod +x /app/docker-entrypoint.sh && \
    chown ${USER}:${USER} /app/docker-entrypoint.sh

# Add additional capability to bind to port 80 and 443 \
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
    # Give write access to /config/caddy and /data/caddy \
    chown -R ${USER}:${USER} /config/caddy /data/caddy; \
    # Give Laravel write access to storage and cache \
    chown -R ${USER}:${USER} /app/storage /app/bootstrap/cache; \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Definido pelo compose
USER ${USER}
