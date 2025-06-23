# Azure Deployment Guide for PantryCRM (Laravel 12 + Filament)

This guide provides step-by-step instructions for deploying the PantryCRM application to Azure App Service with production-ready configuration.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Azure Service Comparison](#azure-service-comparison)
3. [Database Options](#database-options)
4. [Deployment Steps](#deployment-steps)
5. [Environment Configuration](#environment-configuration)
6. [Production Optimizations](#production-optimizations)
7. [Troubleshooting](#troubleshooting)
8. [Cost Estimation](#cost-estimation)

## Prerequisites

- Azure subscription with appropriate permissions
- Azure CLI installed and configured
- Git repository access
- Local development environment with PHP 8.2+

### Install Azure CLI

```bash
# Windows (using winget)
winget install Microsoft.AzureCLI

# macOS
brew install azure-cli

# Linux (Ubuntu/Debian)
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
```

## Azure Service Comparison

### Recommended: Azure App Service ⭐

**Best for**: Most Laravel applications, especially with Filament admin panels

**Pros**:
- Managed PaaS solution
- Automatic scaling and load balancing
- Built-in CI/CD integration
- SSL certificates and custom domains
- Integrated monitoring and logging
- Easy environment variable management

**Cons**:
- Limited server customization
- Platform-specific dependencies

### Azure Container Instances (ACI)

**Best for**: Development environments or simple containerized deployments

**Pros**:
- Serverless container deployment
- Pay-per-second billing
- Quick deployment

**Cons**:
- Limited networking options
- No persistent storage
- Not suitable for database applications

### Azure Kubernetes Service (AKS)

**Best for**: Large-scale, microservices architectures

**Pros**:
- Full container orchestration
- High scalability and availability
- Advanced networking and security

**Cons**:
- Complex setup and management
- Requires Kubernetes expertise
- Higher operational overhead

## Database Options

### Recommended: Azure Database for MySQL Flexible Server ⭐

```bash
# Pricing: ~$20-200+/month depending on configuration
# Best for: Production Laravel applications
```

**Pros**:
- Fully managed service
- Automatic backups and point-in-time restore
- High availability options
- Performance insights and monitoring
- Built-in security features

### Azure Database for PostgreSQL

```bash
# Similar pricing to MySQL
# Best for: Applications requiring advanced database features
```

**Pros**:
- Advanced JSON/JSONB support
- Full-text search capabilities
- Better for complex queries
- Extensions ecosystem

### Keep SQLite (Not Recommended for Production)

**Only suitable for**:
- Development environments
- Proof of concept deployments
- Single-user applications

## Deployment Steps

### Step 1: Login to Azure

```bash
az login
```

### Step 2: Create Resource Group

```bash
az group create \
  --name pantracrm-rg \
  --location "East US"
```

### Step 3: Create App Service Plan

```bash
az appservice plan create \
  --name pantracrm-plan \
  --resource-group pantracrm-rg \
  --is-linux \
  --sku B1
```

**SKU Options**:
- `F1`: Free tier (limited resources)
- `B1`: Basic tier (~$13/month)
- `S1`: Standard tier (~$73/month)
- `P1V2`: Premium tier (~$146/month)

### Step 4: Create Web App

```bash
az webapp create \
  --name pantracrm-app \
  --resource-group pantracrm-rg \
  --plan pantracrm-plan \
  --runtime "PHP|8.3"
```

### Step 5: Create MySQL Database

```bash
# Create MySQL Flexible Server
az mysql flexible-server create \
  --resource-group pantracrm-rg \
  --name pantracrm-mysql \
  --admin-user adminuser \
  --admin-password "YourSecurePassword123!" \
  --sku-name Standard_B1ms \
  --tier Burstable \
  --storage-size 32 \
  --version 8.0 \
  --location "East US"

# Create database
az mysql flexible-server db create \
  --resource-group pantracrm-rg \
  --server-name pantracrm-mysql \
  --database-name pantracrm_db

# Configure firewall for Azure services
az mysql flexible-server firewall-rule create \
  --resource-group pantracrm-rg \
  --name pantracrm-mysql \
  --rule-name AllowAzureServices \
  --start-ip-address 0.0.0.0 \
  --end-ip-address 0.0.0.0
```

### Step 6: Create Redis Cache (Optional but Recommended)

```bash
az redis create \
  --resource-group pantracrm-rg \
  --name pantracrm-redis \
  --location "East US" \
  --sku Basic \
  --vm-size c0
```

### Step 7: Configure App Settings

```bash
az webapp config appsettings set \
  --resource-group pantracrm-rg \
  --name pantracrm-app \
  --settings \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY="base64:$(openssl rand -base64 32)" \
    DB_CONNECTION=mysql \
    DB_HOST=pantracrm-mysql.mysql.database.azure.com \
    DB_PORT=3306 \
    DB_DATABASE=pantracrm_db \
    DB_USERNAME=adminuser \
    DB_PASSWORD="YourSecurePassword123!" \
    CACHE_DRIVER=redis \
    SESSION_DRIVER=redis \
    QUEUE_CONNECTION=redis \
    REDIS_HOST=pantracrm-redis.redis.cache.windows.net \
    REDIS_PASSWORD="$(az redis list-keys --resource-group pantracrm-rg --name pantracrm-redis --query primaryKey -o tsv)" \
    REDIS_PORT=6380 \
    REDIS_TLS=true
```

### Step 8: Configure Startup Script

```bash
az webapp config set \
  --resource-group pantracrm-rg \
  --name pantracrm-app \
  --startup-file "startup.sh"
```

### Step 9: Deploy Application

#### Option A: Deploy from Local Git

```bash
# Configure local git deployment
az webapp deployment source config-local-git \
  --resource-group pantracrm-rg \
  --name pantracrm-app

# Get deployment credentials
az webapp deployment list-publishing-credentials \
  --resource-group pantracrm-rg \
  --name pantracrm-app

# Add Azure remote and push
git remote add azure https://pantracrm-app.scm.azurewebsites.net/pantracrm-app.git
git push azure main
```

#### Option B: Deploy from GitHub

```bash
az webapp deployment source config \
  --resource-group pantracrm-rg \
  --name pantracrm-app \
  --repo-url https://github.com/yourusername/PantryCRM \
  --branch main \
  --manual-integration
```

## Environment Configuration

### Production Environment Variables

```bash
# Core Laravel Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pantracrm-app.azurewebsites.net

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=pantracrm-mysql.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=pantracrm_db
DB_USERNAME=adminuser
DB_PASSWORD=YourSecurePassword123!

# Caching and Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=pantracrm-redis.redis.cache.windows.net
REDIS_PASSWORD=your-redis-key
REDIS_PORT=6380
REDIS_TLS=true

# File Storage (Azure Blob Storage - Optional)
FILESYSTEM_DISK=azure
AZURE_STORAGE_ACCOUNT=your-storage-account
AZURE_STORAGE_KEY=your-storage-key
AZURE_STORAGE_CONTAINER=uploads

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls

# Filament Configuration
FILAMENT_DOMAIN=pantracrm-app.azurewebsites.net
```

### Security Considerations

⚠️ **Important**: Avoid using `$` characters in environment variable values as they can cause issues in Azure App Service.

## Production Optimizations

### Laravel Optimizations

The `startup.sh` script automatically runs these optimizations:

```bash
# Clear existing caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Filament specific optimizations
php artisan filament:optimize

# General Laravel optimizations
php artisan optimize
```

### Manual Optimizations

```bash
# Enable OPcache (add to .user.ini in public folder)
echo "opcache.enable=1" > public/.user.ini
echo "opcache.memory_consumption=256" >> public/.user.ini
echo "opcache.max_accelerated_files=20000" >> public/.user.ini

# Optimize Composer autoloader
composer install --optimize-autoloader --no-dev
```

### Filament Production Setup

1. **Ensure User Authorization**: Update your User model to implement `FilamentUser`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin'); // Implement your logic
    }
}
```

2. **Configure Filament Panels**: Update your AdminPanelProvider:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('/admin')
        ->domain(config('app.url'))
        ->authGuard('web')
        ->login()
        // ... other configurations
}
```

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   ```bash
   # Check firewall rules
   az mysql flexible-server firewall-rule list \
     --resource-group pantracrm-rg \
     --name pantracrm-mysql
   
   # Test connection
   mysql -h pantracrm-mysql.mysql.database.azure.com \
         -u adminuser \
         -p pantracrm_db
   ```

2. **Environment Variable Issues**
   ```bash
   # Check current settings
   az webapp config appsettings list \
     --resource-group pantracrm-rg \
     --name pantracrm-app
   ```

3. **Application Logs**
   ```bash
   # View logs
   az webapp log tail \
     --resource-group pantracrm-rg \
     --name pantracrm-app
   ```

4. **Filament Not Loading**
   - Ensure Filament optimization ran successfully
   - Check that the admin user has proper permissions
   - Verify APP_ENV is set to production

### Performance Monitoring

```bash
# Enable Application Insights
az webapp config appsettings set \
  --resource-group pantracrm-rg \
  --name pantracrm-app \
  --settings \
    APPINSIGHTS_INSTRUMENTATIONKEY=your-instrumentation-key
```

## Cost Estimation

### Monthly Costs (USD)

| Component | Basic | Standard | Premium |
|-----------|-------|----------|---------|
| App Service Plan | $13 | $73 | $146 |
| MySQL Flexible Server | $20 | $60 | $120 |
| Redis Cache | $15 | $75 | $300 |
| **Total** | **$48** | **$208** | **$566** |

### Cost Optimization Tips

1. **Use Basic tier for development/staging**
2. **Scale down during off-hours**
3. **Monitor resource usage regularly**
4. **Use reserved instances for predictable workloads**

## Next Steps

1. **Set up CI/CD pipeline** using GitHub Actions or Azure DevOps
2. **Configure custom domain** and SSL certificate
3. **Implement monitoring and alerting**
4. **Set up automated backups**
5. **Configure staging slots** for zero-downtime deployments

## Additional Resources

- [Azure App Service Documentation](https://docs.microsoft.com/en-us/azure/app-service/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Filament Production Deployment](https://filamentphp.com/docs/deployment)
- [Azure MySQL Documentation](https://docs.microsoft.com/en-us/azure/mysql/)

---

For any deployment issues, refer to the `TROUBLESHOOTING.md` file in this repository or the Azure support documentation.