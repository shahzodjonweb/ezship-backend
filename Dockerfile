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

# Set default database configuration
RUN echo "DB_CONNECTION=pgsql" >> /var/www/html/.env && \
    echo "DB_HOST=127.0.0.1" >> /var/www/html/.env && \
    echo "DB_PORT=5432" >> /var/www/html/.env && \
    echo "CACHE_DRIVER=array" >> /var/www/html/.env && \
    echo "SESSION_DRIVER=array" >> /var/www/html/.env && \
    echo "QUEUE_CONNECTION=sync" >> /var/www/html/.env

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist \
    && npm install \
    && npm run build || true \
    && npm cache clean --force \
    && rm -rf node_modules

# Generate application key
RUN php artisan key:generate || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

# Make entrypoint optional - only run if file exists
ENTRYPOINT ["/bin/sh", "-c", "if [ -f /usr/local/bin/entrypoint.sh ]; then exec /usr/local/bin/entrypoint.sh /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf; else exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf; fi"]