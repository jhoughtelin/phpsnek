FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Configure Apache
RUN echo 'ServerName battlesnake' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername 

# Configure PHP
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Expose port
EXPOSE 80

# Set up entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

# Start Apache
CMD ["apache2-foreground"]
