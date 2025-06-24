#!/bin/bash

# Set bash to exit on the first error.
set -e

# --- Laravel Application Setup ---
echo "--- Starting Laravel Application Setup ---"
cd /home/site/wwwroot

# Copy environment configuration if it exists
if [ -f ".env.azure" ]; then
    echo "Found .env.azure, copying to .env"
    cp .env.azure .env
fi

# Run Laravel setup commands
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Setting directory permissions..."
chmod -R 775 storage bootstrap/cache

echo "Clearing application caches..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Running database migrations and seeding..."
php artisan migrate --force
php artisan db:seed --force

echo "Optimizing Filament..."
php artisan filament:optimize

echo "--- Laravel Application Setup Completed ---"