# PHP Production Dockerfile for Laravel Application
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    mysql-client \
    postgresql-client \
    redis \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    supervisor \
    nginx \
    certbot \
    certbot-nginx \
    openssl \
    ca-certificates \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    imagemagick-dev \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pgsql \
        zip \
        bcmath \
        mbstring \
        xml \
        fileinfo \
        intl \
        exif \
        pcntl \
        sockets \
        opcache \
        gd \
        redis

# Configure PHP for production
COPY infrastructure/production/config/php/php.ini /usr/local/etc/php/conf.d/php-prod.ini
COPY infrastructure/production/config/php/docker.conf /usr/local/etc/php-fpm.d/docker.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create app user
RUN addgroup -g 1000 app && \
    adduser -u 1000 -G app -s /bin/sh -D app && \
    chown -R app:app /var/www/html

# Switch to app user
USER app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (skip dev dependencies)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application code
COPY --chown=app:app . .

# Copy production and tenant environment files
COPY --chown=app:app .env.production /var/www/html/.env.production

# Install Node dependencies and build assets
RUN npm install && npm run build

# Switch back to root for system operations
USER root

# Create necessary directories
RUN mkdir -p /var/www/html/storage/framework/cache \ && \
    mkdir -p /var/www/html/storage/framework/sessions \ && \
    mkdir -p /var/www/html/storage/framework/views \ && \
    mkdir -p /var/www/html/storage/logs \ && \
    chown -R app:app /var/www/html

# Configure supervisor for cron and queue
COPY infrastructure/production/config/supervisor/supervisord.conf /etc/supervisord.conf

# Copy startup script
COPY infrastructure/production/scripts/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health-check || exit 1

# Expose php-fpm port
EXPOSE 9000

# Start command
CMD ["/usr/local/bin/start.sh"]