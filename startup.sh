#!/bin/bash
# Azure App Service - Laravel Startup Script

# Exit on first error
set -e

echo "--- Starting Laravel Application Setup ---"
cd /home/site/wwwroot

# --- Nginx Configuration ---
# This is the critical step: Overwrite the default Nginx config
# and then gracefully reload the service to apply the changes.
echo "Copying custom Nginx configuration to /etc/nginx/sites-enabled/default..."
cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-enabled/default

echo "Reloading Nginx to apply new configuration..."
nginx -s reload

# Copy environment file if it exists
if [ -f ".env.azure" ]; then
    echo "Found .env.azure, copying to .env"
    cp .env.azure .env
fi

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create SQLite database file and set permissions
echo "Creating SQLite database file and setting permissions..."
mkdir -p database
touch database/database.sqlite
chmod -R 775 storage bootstrap/cache
chmod 777 database
chmod 666 database/database.sqlite

# Run database migrations and seeders
echo "Running database migrations and seeding..."
php artisan migrate --force
php artisan db:seed --force

# Optimize Laravel application
echo "Optimizing Laravel application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan filament:optimize

echo "--- Laravel Application Setup Completed ---"
# The Azure platform will now start Nginx and PHP-FPM using our custom config.