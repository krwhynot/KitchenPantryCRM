#!/bin/bash
# Azure App Service - Laravel Startup Script

# Exit on first error
set -e

echo "--- Starting Laravel Application Setup ---"
cd /home/site/wwwroot

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
# The Azure platform will find the nginx.conf in the nginx/ directory
# and will start Nginx and PHP-FPM automatically.