# Multi-stage build for production
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    redis \
    supervisor \
    nginx \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-webp \
    --with-xpm \
    --with-freetype

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    pgsql \
    mysqli \
    gd \
    zip \
    bcmath \
    opcache \
    pcntl

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Production build stage
FROM base AS production

# Copy application files
COPY . /var/www/html

# Copy .env for production if it doesn't exist
RUN if [ ! -f /var/www/html/.env ]; then \
    cp /var/www/html/.env.production /var/www/html/.env || \
    cp /var/www/html/.env.example /var/www/html/.env; \
    fi

# Set default database configuration if not present
RUN if ! grep -q "^DB_CONNECTION=" /var/www/html/.env 2>/dev/null; then \
    echo "DB_CONNECTION=pgsql" >> /var/www/html/.env; fi && \
    if ! grep -q "^DB_HOST=" /var/www/html/.env 2>/dev/null; then \
    echo "DB_HOST=postgres" >> /var/www/html/.env; fi && \
    if ! grep -q "^DB_PORT=" /var/www/html/.env 2>/dev/null; then \
    echo "DB_PORT=5432" >> /var/www/html/.env; fi

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist \
    && npm install \
    && npm run build || true \
    && npm cache clean --force \
    && rm -rf node_modules

# Generate application key
RUN php artisan key:generate || true

# Create necessary directories
RUN mkdir -p /var/log/supervisor /var/log/php-fpm /var/log/nginx /var/log/php

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisor/supervisord.prod.conf /etc/supervisor/conf.d/supervisord.prod.conf
COPY docker/supervisor/supervisord.app-only.conf /etc/supervisor/conf.d/supervisord.app-only.conf

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]