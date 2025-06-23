#!/bin/bash

# Azure App Service startup script for Laravel application
echo "Starting PantryCRM Laravel application..."

# Navigate to the application directory
cd /home/site/wwwroot

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database file if it doesn't exist
if [ ! -f "database/database.sqlite" ]; then
    echo "Creating SQLite database file..."
    touch database/database.sqlite
fi

# Set proper permissions
chmod 777 database
chmod 666 database/database.sqlite

# Clear and optimize Laravel caches
echo "Optimizing Laravel application..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Run database seeders (only if tables are empty)
echo "Seeding database if needed..."
php artisan db:seed --force

# Optimize Filament
echo "Optimizing Filament..."
php artisan filament:optimize

# Create custom NGINX configuration for Laravel
echo "Configuring NGINX for Laravel..."
cat > /home/site/nginx.conf <<'EOF'
server {
    listen 8080;
    listen [::]:8080;
    root /home/site/wwwroot/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
EOF

# Copy the custom NGINX configuration
cp /home/site/nginx.conf /etc/nginx/sites-available/default

# Restart NGINX
echo "Restarting NGINX..."
service nginx restart

echo "PantryCRM startup completed successfully!"