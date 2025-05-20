#!/bin/bash
set -e

# Wait for any pre-existing apache processes to end
if [ -f /var/run/apache2/apache2.pid ]; then
    echo "Waiting for Apache to stop..."
    service apache2 stop
    while [ -f /var/run/apache2/apache2.pid ]; do
        sleep 1
    done
fi

# Update Apache document root
if [ -n "$APACHE_DOCUMENT_ROOT" ]; then
    echo "Setting Apache document root to $APACHE_DOCUMENT_ROOT"
    sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
    sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
fi

# Install dependencies if they're not installed yet
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Installing dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Ensure correct permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Execute the command passed to docker run
exec "$@"
