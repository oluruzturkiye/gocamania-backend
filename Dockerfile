FROM php:8.1-apache

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

# Enable Apache modules
RUN a2enmod rewrite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . /var/www/html/

# Create cache directory and set permissions
RUN mkdir -p /var/www/html/bootstrap/cache && chmod -R 777 /var/www/html/bootstrap/cache
RUN mkdir -p /var/www/html/storage && chmod -R 777 /var/www/html/storage

# Copy .env.example to .env
RUN cp .env.example .env

# Install dependencies
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Generate application key
RUN php artisan key:generate --force

# Change ownership of our applications
RUN chown -R www-data:www-data /var/www/html

# Copy apache vhost file
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
