FROM dunglas/frankenphp:1.10.1-php8.4 AS base

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_mysql gd intl zip opcache


# Build stage
FROM base AS builder

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader


# Production stage
FROM base AS production

WORKDIR /app

COPY --from=builder /app .

RUN chown -R www-data:www-data /app

USER www-data
