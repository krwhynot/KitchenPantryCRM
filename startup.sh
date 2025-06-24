#!/bin/bash

# Set bash to exit on the first error, but be more robust
set -e
set -o pipefail

# Add logging
exec 1> >(logger -s -t STARTUP_SCRIPT)
exec 2>&1

# --- Laravel Application Setup ---
echo "Starting PantryCRM Laravel application setup..."
cd /home/site/wwwroot

# --- Custom PHP Configuration ---
echo "Applying custom PHP configuration..."
PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d/
CUSTOM_PHP_INI_PATH=/home/site/wwwroot/.azure/config/php/custom.ini

if [ -f "$CUSTOM_PHP_INI_PATH" ]; then
    cp "$CUSTOM_PHP_INI_PATH" "$PHP_INI_SCAN_DIR"
    echo "Custom PHP INI applied. SQLite extensions should now be enabled."
else
    echo "Warning: Custom PHP INI file not found at $CUSTOM_PHP_INI_PATH"
fi

# Copy environment configuration
echo "Setting up environment configuration..."
if [ -f ".env.azure" ]; then
    cp .env.azure .env
    echo "Copied .env.azure to .env"
else
    echo "Warning: .env.azure not found"
fi

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
mkdir -p database
if [ ! -f "database/database.sqlite" ]; then
    echo "Creating SQLite database file..."
    touch database/database.sqlite
fi
echo "Setting directory permissions..."
chmod -R 775 storage bootstrap/cache
chmod 777 database
chmod 666 database/database.sqlite
echo "Clearing all Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "Building optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Running database migrations..."
php artisan migrate --force
echo "Seeding database if needed..."
php artisan db:seed --force
echo "Optimizing Filament..."
php artisan filament:optimize
echo "PantryCRM application setup completed successfully!"
echo "-------------------------------------------------"

# --- Nginx & PHP-FPM Server Startup ---
echo "Starting server configuration and services..."

# 1. Replace the default Nginx config with our custom one.
echo "Copying custom Nginx configuration..."
cp /home/site/wwwroot/nginx-default.conf /etc/nginx/sites-available/default

# 2. Start the PHP-FPM service for the correct PHP version.
echo "Starting PHP-FPM service..."
service php8.3-fpm start

# 3. Start the Nginx web server in the foreground.
# This is the primary process that will keep the container running.
echo "Starting Nginx in foreground mode..."
nginx -g 'daemon off;'