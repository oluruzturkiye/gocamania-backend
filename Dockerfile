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

# Create .env file with necessary configurations
RUN echo "APP_NAME=Gocamania\n\
APP_ENV=production\n\
APP_DEBUG=true\n\
APP_URL=https://web-production-cef5.up.railway.app\n\
ASSET_URL=https://web-production-cef5.up.railway.app\n\
LOG_CHANNEL=stack\n\
LOG_DEPRECATIONS_CHANNEL=null\n\
LOG_LEVEL=debug\n\
DB_CONNECTION=mysql\n\
DB_HOST=mysql.railway.internal\n\
DB_PORT=3306\n\
DB_DATABASE=railway\n\
DB_USERNAME=root\n\
DB_PASSWORD=QIGLpzPSotrZfzZFIYIOtVTndRQMWkHw\n\
BROADCAST_DRIVER=log\n\
CACHE_DRIVER=file\n\
FILESYSTEM_DISK=public\n\
QUEUE_CONNECTION=sync\n\
SESSION_DRIVER=file\n\
SESSION_LIFETIME=120\n\
GOOGLE_CLIENT_ID=1059420104982-4hofs7q0htsglsr684ovuq87e2lcuc8s.apps.googleusercontent.com\n\
GOOGLE_CLIENT_SECRET=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\n\
GOOGLE_REDIRECT_URI=https://web-production-cef5.up.railway.app/auth/google/callback" > .env

# Install dependencies
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/storage
RUN chmod -R 777 /var/www/html/bootstrap/cache

# Generate application key and store it
RUN php artisan key:generate --force

# Cache configuration and routes for better performance
RUN php artisan config:cache
RUN php artisan route:cache

# Create storage link
RUN php artisan storage:link

# Run migrations (only if database is available)
RUN if [ -n "$DB_HOST" ]; then php artisan migrate --force; fi

# Configure Apache DocumentRoot and error logging
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
ENV APACHE_LOG_DIR /var/log/apache2

# Set ServerName to suppress FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache modules and configure PHP
RUN a2enmod rewrite headers

# Configure PHP for development
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# Create PHP configuration
RUN echo "display_errors=On\n\
error_reporting=E_ALL\n\
log_errors=On\n\
error_log=/var/log/php_errors.log\n\
memory_limit=256M\n\
upload_max_filesize=64M\n\
post_max_size=64M\n\
max_execution_time=300" > /usr/local/etc/php/conf.d/app.ini

# Create Apache virtual host configuration with detailed error logging
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel debug\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks MultiViews\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
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

# Final setup and permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

# Start Apache with debug output and tail the error log
CMD (tail -f /var/log/apache2/error.log & tail -f /var/log/php_errors.log & apache2-foreground)
