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
# Using 'daemon off;' ensures the script doesn't exit, which would stop the container.
echo "Starting Nginx service..."
service nginx start

# 4. Keep the container running by tailing logs
echo "Startup complete. Tailing Nginx logs to keep container alive."
tail -f /var/log/nginx/access.log /var/log/nginx/error.log