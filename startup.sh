#!/bin/bash

# Azure App Service startup script for Laravel application
echo "Starting PantryCRM Laravel application..."

# Navigate to the application directory
cd /home/site/wwwroot

# Install production dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database file if it doesn't exist
if [ ! -f "database/database.sqlite" ]; then
    echo "Creating SQLite database file..."
    touch database/database.sqlite
fi

# Set critical permissions for Laravel
echo "Setting directory permissions..."
chmod -R 775 storage bootstrap/cache
chmod 777 database
chmod 666 database/database.sqlite

# Clear ALL caches to prevent stale data issues
echo "Clearing all Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
echo "Building optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Run database seeders (only if tables are empty)
echo "Seeding database if needed..."
php artisan db:seed --force

# Optimize Filament for production
echo "Optimizing Filament..."
php artisan filament:optimize

echo "PantryCRM startup completed successfully!"