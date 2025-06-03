# Stage 1: Build stage
FROM composer:2.6 AS build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 2: Runtime stage
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/laravel.ini

# Copy Apache virtual host config
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files from build stage
COPY --from=build /app .

# Create required directories and set permissions
# RUN mkdir -p storage/framework/sessions \
#     && mkdir -p storage/framework/views \
#     && mkdir -p storage/framework/cache \
#     && mkdir -p bootstrap/cache \
#     && chown -R www-data:www-data /var/www/html \
#     && chmod -R 775 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
