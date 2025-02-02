FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy existing application directory
COPY . /app/

# Create cache directory and set permissions
RUN mkdir -p /app/bootstrap/cache && chmod -R 777 /app/bootstrap/cache
RUN mkdir -p /app/storage && chmod -R 777 /app/storage

# Copy .env.example to .env
RUN cp .env.example .env

# Install dependencies
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Generate application key
RUN php artisan key:generate --force

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
