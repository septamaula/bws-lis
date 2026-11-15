# ==============================================================================
# BWS LIS - LARAVEL 13 BACKEND DOCKERFILE
# ==============================================================================
FROM php:8.3-cli-alpine AS development

# Install system dependencies & PostgreSQL client tools
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-client \
    linux-headers \
    dos2unix

# Install PHP extensions required by Laravel, Spatie Permission & PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    zip \
    intl \
    opcache \
    pcntl

# Install Composer binary
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy entrypoint script and ensure Unix line endings
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN dos2unix /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy dependency definitions and install (for container caching)
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader || true

# Copy application source code
COPY . .

# Set permissions for Laravel runtime storage & cache
RUN mkdir -p storage/framework/cache/data \
             storage/framework/cache/locks \
             storage/framework/sessions \
             storage/framework/views \
             storage/framework/testing \
             storage/logs \
             bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
