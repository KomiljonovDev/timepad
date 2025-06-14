# Use the official PHP image with necessary extensions
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && pecl install redis && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring exif pcntl bcmath gd simplexml

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy existing application code
COPY .. .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
