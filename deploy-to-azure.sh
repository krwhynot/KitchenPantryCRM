#!/bin/bash

# Azure Deployment Script for PantryCRM (Laravel 12 + Filament)
# This script automates the deployment of PantryCRM to Azure App Service

set -e  # Exit on any error

# Configuration Variables
RESOURCE_GROUP="pantracrm-rg"
LOCATION="East US"
APP_SERVICE_PLAN="pantracrm-plan"
WEB_APP_NAME="pantracrm-app"
MYSQL_SERVER_NAME="pantracrm-mysql"
REDIS_CACHE_NAME="pantracrm-redis"
DATABASE_NAME="pantracrm_db"
ADMIN_USERNAME="adminuser"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper function for colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Azure CLI is installed
check_azure_cli() {
    print_status "Checking Azure CLI installation..."
    if ! command -v az &> /dev/null; then
        print_error "Azure CLI is not installed. Please install it first."
        echo "Visit: https://docs.microsoft.com/en-us/cli/azure/install-azure-cli"
        exit 1
    fi
    print_success "Azure CLI is installed"
}

# Check if user is logged in to Azure
check_azure_login() {
    print_status "Checking Azure login status..."
    if ! az account show &> /dev/null; then
        print_warning "Not logged in to Azure. Please log in..."
        az login
    fi
    
    # Display current subscription
    SUBSCRIPTION=$(az account show --query name -o tsv)
    print_success "Logged in to Azure. Current subscription: $SUBSCRIPTION"
}

# Prompt for admin password
get_admin_password() {
    while true; do
        echo -n "Enter MySQL admin password (min 8 chars, must include uppercase, lowercase, number): "
        read -s ADMIN_PASSWORD
        echo
        
        if [[ ${#ADMIN_PASSWORD} -ge 8 ]] && [[ "$ADMIN_PASSWORD" =~ [A-Z] ]] && [[ "$ADMIN_PASSWORD" =~ [a-z] ]] && [[ "$ADMIN_PASSWORD" =~ [0-9] ]]; then
            break
        else
            print_error "Password does not meet requirements. Please try again."
        fi
    done
}

# Create resource group
create_resource_group() {
    print_status "Creating resource group: $RESOURCE_GROUP"
    az group create \
        --name "$RESOURCE_GROUP" \
        --location "$LOCATION"
    print_success "Resource group created"
}

# Create App Service Plan
create_app_service_plan() {
    print_status "Creating App Service Plan: $APP_SERVICE_PLAN"
    az appservice plan create \
        --name "$APP_SERVICE_PLAN" \
        --resource-group "$RESOURCE_GROUP" \
        --is-linux \
        --sku B1
    print_success "App Service Plan created"
}

# Create Web App
create_web_app() {
    print_status "Creating Web App: $WEB_APP_NAME"
    az webapp create \
        --name "$WEB_APP_NAME" \
        --resource-group "$RESOURCE_GROUP" \
        --plan "$APP_SERVICE_PLAN" \
        --runtime "PHP|8.3"
    print_success "Web App created"
}

# Create MySQL Flexible Server
create_mysql_server() {
    print_status "Creating MySQL Flexible Server: $MYSQL_SERVER_NAME"
    az mysql flexible-server create \
        --resource-group "$RESOURCE_GROUP" \
        --name "$MYSQL_SERVER_NAME" \
        --admin-user "$ADMIN_USERNAME" \
        --admin-password "$ADMIN_PASSWORD" \
        --sku-name Standard_B1ms \
        --tier Burstable \
        --storage-size 32 \
        --version 8.0 \
        --location "$LOCATION" \
        --yes
    print_success "MySQL server created"
}

# Create database
create_database() {
    print_status "Creating database: $DATABASE_NAME"
    az mysql flexible-server db create \
        --resource-group "$RESOURCE_GROUP" \
        --server-name "$MYSQL_SERVER_NAME" \
        --database-name "$DATABASE_NAME"
    print_success "Database created"
}

# Configure MySQL firewall
configure_mysql_firewall() {
    print_status "Configuring MySQL firewall rules..."
    az mysql flexible-server firewall-rule create \
        --resource-group "$RESOURCE_GROUP" \
        --name "$MYSQL_SERVER_NAME" \
        --rule-name AllowAzureServices \
        --start-ip-address 0.0.0.0 \
        --end-ip-address 0.0.0.0
    print_success "MySQL firewall configured"
}

# Create Redis Cache
create_redis_cache() {
    print_status "Creating Redis Cache: $REDIS_CACHE_NAME"
    az redis create \
        --resource-group "$RESOURCE_GROUP" \
        --name "$REDIS_CACHE_NAME" \
        --location "$LOCATION" \
        --sku Basic \
        --vm-size c0
    print_success "Redis Cache created"
}

# Configure App Settings
configure_app_settings() {
    print_status "Configuring application settings..."
    
    # Generate Laravel app key
    APP_KEY=$(openssl rand -base64 32)
    
    # Get Redis connection details
    REDIS_HOST="$REDIS_CACHE_NAME.redis.cache.windows.net"
    REDIS_PASSWORD=$(az redis list-keys --resource-group "$RESOURCE_GROUP" --name "$REDIS_CACHE_NAME" --query primaryKey -o tsv)
    
    # Set application settings
    az webapp config appsettings set \
        --resource-group "$RESOURCE_GROUP" \
        --name "$WEB_APP_NAME" \
        --settings \
            APP_ENV=production \
            APP_DEBUG=false \
            APP_KEY="base64:$APP_KEY" \
            DB_CONNECTION=mysql \
            DB_HOST="$MYSQL_SERVER_NAME.mysql.database.azure.com" \
            DB_PORT=3306 \
            DB_DATABASE="$DATABASE_NAME" \
            DB_USERNAME="$ADMIN_USERNAME" \
            DB_PASSWORD="$ADMIN_PASSWORD" \
            CACHE_DRIVER=redis \
            SESSION_DRIVER=redis \
            QUEUE_CONNECTION=redis \
            REDIS_HOST="$REDIS_HOST" \
            REDIS_PASSWORD="$REDIS_PASSWORD" \
            REDIS_PORT=6380 \
            REDIS_TLS=true \
            FILAMENT_DOMAIN="$WEB_APP_NAME.azurewebsites.net"
    
    print_success "Application settings configured"
}

# Configure startup script
configure_startup_script() {
    print_status "Configuring startup script..."
    az webapp config set \
        --resource-group "$RESOURCE_GROUP" \
        --name "$WEB_APP_NAME" \
        --startup-file "startup.sh"
    print_success "Startup script configured"
}

# Setup deployment
setup_deployment() {
    print_status "Setting up deployment from local Git..."
    
    # Configure local git deployment
    az webapp deployment source config-local-git \
        --resource-group "$RESOURCE_GROUP" \
        --name "$WEB_APP_NAME"
    
    # Get deployment URL
    DEPLOYMENT_URL=$(az webapp deployment source config-local-git \
        --resource-group "$RESOURCE_GROUP" \
        --name "$WEB_APP_NAME" \
        --query url -o tsv)
    
    print_success "Deployment configured"
    print_warning "To deploy your code, run:"
    echo "git remote add azure $DEPLOYMENT_URL"
    echo "git push azure main"
}

# Display deployment summary
show_deployment_summary() {
    APP_URL="https://$WEB_APP_NAME.azurewebsites.net"
    
    print_success "Deployment completed successfully!"
    echo
    echo "=== Deployment Summary ==="
    echo "Resource Group: $RESOURCE_GROUP"
    echo "Web App: $WEB_APP_NAME"
    echo "App URL: $APP_URL"
    echo "MySQL Server: $MYSQL_SERVER_NAME.mysql.database.azure.com"
    echo "Database: $DATABASE_NAME"
    echo "Redis Cache: $REDIS_CACHE_NAME.redis.cache.windows.net"
    echo
    echo "=== Next Steps ==="
    echo "1. Deploy your code using Git:"
    echo "   git remote add azure $DEPLOYMENT_URL"
    echo "   git push azure main"
    echo
    echo "2. Visit your application:"
    echo "   $APP_URL"
    echo
    echo "3. Access Filament admin panel:"
    echo "   $APP_URL/admin"
    echo
    echo "=== Important Notes ==="
    echo "- Database password: [HIDDEN FOR SECURITY]"
    echo "- Make sure to run migrations after deployment"
    echo "- Create an admin user for Filament access"
    echo "- Monitor application logs for any issues"
}

# Main deployment function
main() {
    echo "========================================"
    echo "PantryCRM Azure Deployment Script"
    echo "========================================"
    echo
    
    check_azure_cli
    check_azure_login
    get_admin_password
    
    echo
    print_status "Starting deployment process..."
    echo
    
    create_resource_group
    create_app_service_plan
    create_web_app
    create_mysql_server
    create_database
    configure_mysql_firewall
    create_redis_cache
    configure_app_settings
    configure_startup_script
    setup_deployment
    
    echo
    show_deployment_summary
}

# Handle script interruption
cleanup() {
    print_warning "Script interrupted. You may need to clean up resources manually."
    exit 1
}

trap cleanup INT

# Run main function
main "$@"