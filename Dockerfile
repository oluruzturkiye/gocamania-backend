FROM php:8.2-apache

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
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache

# Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
ENV APACHE_LOG_DIR /var/log/apache2

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP for debugging
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i \
    -e 's/upload_max_filesize = 2M/upload_max_filesize = 64M/g' \
    -e 's/post_max_size = 8M/post_max_size = 64M/g' \
    -e 's/memory_limit = 128M/memory_limit = 512M/g' \
    -e 's/max_execution_time = 30/max_execution_time = 300/g' \
    -e 's/display_errors = Off/display_errors = On/g' \
    -e 's/log_errors = Off/log_errors = On/g' \
    "$PHP_INI_DIR/php.ini"

# Configure Apache virtual host with detailed error logging
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot ${APACHE_DOCUMENT_ROOT}\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel debug\n\
\n\
    <Directory ${APACHE_DOCUMENT_ROOT}>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    php_flag display_errors on\n\
    php_flag log_errors on\n\
    php_value error_log /var/log/apache2/php_errors.log\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create log directory and set permissions
RUN mkdir -p ${APACHE_LOG_DIR} \
    && touch ${APACHE_LOG_DIR}/error.log \
    && touch ${APACHE_LOG_DIR}/access.log \
    && touch ${APACHE_LOG_DIR}/php_errors.log \
    && chown -R www-data:www-data ${APACHE_LOG_DIR} \
    && chmod -R 755 ${APACHE_LOG_DIR}

# Create a test PHP file
RUN echo "<?php\n\
error_reporting(E_ALL);\n\
ini_set('display_errors', 1);\n\
phpinfo();" > ${APACHE_DOCUMENT_ROOT}/info.php

# Start Apache in foreground with debug output
CMD ["apache2-foreground"]

EXPOSE 80
