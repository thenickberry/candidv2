FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libexif-dev \
    libheif-dev \
    libmagickwand-dev \
    imagemagick \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure and install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        exif \
        zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick

# Set working directory
WORKDIR /var/www

# Copy custom PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy composer files first (for better caching)
COPY composer.json /var/www/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Copy application files
COPY public/ /var/www/public/
COPY src/ /var/www/src/
COPY config/ /var/www/config/
COPY resources/ /var/www/resources/
COPY bootstrap/ /var/www/bootstrap/
COPY .env /var/www/.env

# Create storage directories
RUN mkdir -p /var/www/storage/uploads /var/www/storage/images /var/www/storage/cache /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 755 /var/www/storage

EXPOSE 9000
