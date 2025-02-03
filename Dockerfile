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

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 64M/g' "$PHP_INI_DIR/php.ini"
RUN sed -i 's/post_max_size = 8M/post_max_size = 64M/g' "$PHP_INI_DIR/php.ini"
RUN sed -i 's/memory_limit = 128M/memory_limit = 512M/g' "$PHP_INI_DIR/php.ini"
RUN sed -i 's/max_execution_time = 30/max_execution_time = 300/g' "$PHP_INI_DIR/php.ini"

# Enable Apache modules
RUN a2enmod rewrite headers

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Create cache directory and set permissions
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
RUN chmod -R 777 storage bootstrap/cache

# Copy .env.example to .env
RUN cp .env.example .env

# Generate application key
RUN php artisan key:generate --force

# Run migrations (only if database is available)
RUN if [ -n "$DB_HOST" ]; then php artisan migrate --force; fi

# Change ownership of our applications
RUN chown -R www-data:www-data /var/www/html

# Configure Apache DocumentRoot and error logging
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
ENV APACHE_LOG_DIR /var/log/apache2

# Set ServerName to suppress FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create Apache virtual host configuration with detailed error logging
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel debug\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    php_flag display_errors on\n\
    php_flag display_startup_errors on\n\
    php_value error_reporting E_ALL\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create .htaccess file
RUN echo $'<IfModule mod_rewrite.c>\n\
    <IfModule mod_negotiation.c>\n\
        Options -MultiViews -Indexes\n\
    </IfModule>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^ index.php [L]\n\
</IfModule>' > /var/www/html/public/.htaccess

EXPOSE 80

# Start Apache with debug output
CMD ["apache2-foreground"]
