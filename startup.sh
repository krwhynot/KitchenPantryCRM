#!/bin/bash

# Set bash to exit on the first error and fail on pipe errors.
set -e
set -o pipefail

# --- Laravel Application Setup ---
echo "--- Starting PantryCRM Laravel application setup ---"
cd /home/site/wwwroot

# --- Custom PHP Configuration ---
echo "Applying custom PHP configuration for SQLite..."
PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d/
CUSTOM_PHP_INI_PATH=/home/site/wwwroot/.azure/config/php/custom.ini

if [ -f "$CUSTOM_PHP_INI_PATH" ]; then
    cp "$CUSTOM_PHP_INI_PATH" "$PHP_INI_SCAN_DIR"
    echo "Custom PHP INI for SQLite applied."
else
    echo "Warning: Custom PHP INI file not found at $CUSTOM_PHP_INI_PATH"
fi

# Copy environment configuration
if [ -f ".env.azure" ]; then
    cp .env.azure .env
    echo "Copied .env.azure to .env"
fi

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Creating SQLite database file and setting permissions..."
mkdir -p database
touch database/database.sqlite
chmod -R 775 storage bootstrap/cache
chmod 777 database
chmod 666 database/database.sqlite

echo "Clearing and caching routes and views..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan route:cache
php artisan view:cache

echo "Running database migrations and seeding..."
php artisan migrate --force
php artisan db:seed --force

echo "Optimizing Filament..."
php artisan filament:optimize

echo "--- PantryCRM application setup completed successfully! ---"

# --- Nginx & PHP-FPM Server Startup ---
echo "--- Starting server configuration and services ---"

# 1. Replace the default Nginx config with our custom one.
echo "Copying custom Nginx configuration..."
cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-available/default

# 2. Start the PHP-FPM service for the correct PHP version.
echo "Starting PHP-FPM service..."
service php8.3-fpm start

# 3. Start the Nginx web server in the foreground.
# This is the primary process that will keep the container running.
echo "--- Starting Nginx in foreground mode... ---"
nginx -g 'daemon off;'