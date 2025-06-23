# Production Deployment Checklist for PantryCRM

This checklist ensures your Laravel 12 + Filament application is properly configured for production deployment on Azure.

## Pre-Deployment Checklist

### 🔐 Security Configuration

- [ ] **Environment Variables**: All sensitive data moved from `.env` to Azure App Settings
- [ ] **APP_DEBUG**: Set to `false` in production
- [ ] **APP_ENV**: Set to `production`
- [ ] **Database Credentials**: Using Azure MySQL with strong passwords
- [ ] **Redis Password**: Configured with Azure Redis Cache credentials
- [ ] **SSL/HTTPS**: Enabled on Azure App Service (automatic)
- [ ] **Filament Authorization**: Implemented `FilamentUser` interface for user access control

### 🗃️ Database Configuration

- [ ] **Database Type**: Azure Database for MySQL Flexible Server configured
- [ ] **Connection Charset**: Set to `utf8mb4`
- [ ] **Connection Collation**: Set to `utf8mb4_unicode_ci`
- [ ] **Firewall Rules**: Configured to allow Azure services
- [ ] **Backup Strategy**: Automated backups enabled
- [ ] **Connection Pooling**: Configured if needed for high traffic

### ⚡ Performance Optimization

- [ ] **Laravel Caching**: 
  - [ ] `CACHE_DRIVER=redis`
  - [ ] `SESSION_DRIVER=redis`
  - [ ] `QUEUE_CONNECTION=redis`
- [ ] **Artisan Commands**: 
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] `php artisan filament:optimize`
- [ ] **Composer Optimization**: `composer install --optimize-autoloader --no-dev`
- [ ] **OPcache**: Enabled in production
- [ ] **Redis Cache**: Azure Redis Cache configured and connected

### 📁 File Storage

- [ ] **Storage Symlink**: Created (`php artisan storage:link`)
- [ ] **File Permissions**: Proper permissions for `storage/` and `bootstrap/cache/`
- [ ] **Azure Blob Storage**: Configured if using cloud file storage (optional)
- [ ] **Public Assets**: All assets compiled and optimized (`npm run build`)

### 🔧 Application Configuration

- [ ] **Startup Script**: `startup.sh` configured in Azure App Service
- [ ] **Web.config**: Proper URL rewriting configuration
- [ ] **Application Key**: Generated and secure
- [ ] **Timezone**: Configured correctly
- [ ] **Locale**: Set appropriately for your region

## Post-Deployment Checklist

### 🚀 Deployment Verification

- [ ] **Application Loads**: Homepage accessible without errors
- [ ] **Filament Admin**: Admin panel accessible at `/admin`
- [ ] **Database Connectivity**: Application can connect to Azure MySQL
- [ ] **Redis Connectivity**: Caching and sessions working properly
- [ ] **Asset Loading**: CSS/JS files loading correctly
- [ ] **SSL Certificate**: HTTPS working and certificate valid

### 👤 User Management

- [ ] **Admin User Created**: At least one admin user exists
- [ ] **User Authentication**: Login/logout functionality working
- [ ] **Role Permissions**: Proper access control implemented
- [ ] **Password Reset**: Email functionality working (if configured)

### 📊 Monitoring & Logging

- [ ] **Application Logs**: Logs writing to proper location
- [ ] **Error Tracking**: Error reporting configured
- [ ] **Performance Monitoring**: Azure Application Insights configured (optional)
- [ ] **Health Checks**: Application health endpoint configured
- [ ] **Backup Verification**: Database backups running successfully

### 🔄 CI/CD Pipeline

- [ ] **GitHub Actions**: Automated deployment workflow configured
- [ ] **Deployment Slots**: Staging slot configured (optional)
- [ ] **Environment Promotion**: Process for promoting staging to production
- [ ] **Rollback Strategy**: Plan for reverting deployments if needed

## Azure-Specific Configuration

### App Service Settings

```bash
# Essential App Settings in Azure Portal
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key
DB_CONNECTION=mysql
DB_HOST=your-mysql-server.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis.redis.cache.windows.net
REDIS_PASSWORD=your_redis_key
REDIS_PORT=6380
REDIS_TLS=true
FILAMENT_DOMAIN=your-app.azurewebsites.net
```

### Platform Configuration

- [ ] **PHP Version**: Set to PHP 8.3
- [ ] **Platform**: Linux selected
- [ ] **Startup Command**: Set to `startup.sh`
- [ ] **Always On**: Enabled to prevent cold starts
- [ ] **ARR Affinity**: Disabled for better performance

## Testing Checklist

### 🧪 Functional Testing

- [ ] **CRUD Operations**: All Create, Read, Update, Delete operations working
- [ ] **File Uploads**: File upload functionality working
- [ ] **Search & Filters**: All search and filter features working
- [ ] **Exports/Imports**: Data export/import functionality working
- [ ] **Email Notifications**: Email sending working (if configured)

### ⚡ Performance Testing

- [ ] **Page Load Times**: All pages load within acceptable time limits
- [ ] **Database Queries**: No N+1 query problems
- [ ] **Memory Usage**: Application memory usage within limits
- [ ] **Cache Hit Rates**: Redis cache performing as expected

### 🔒 Security Testing

- [ ] **Authentication**: Cannot access admin without proper credentials
- [ ] **Authorization**: Users can only access appropriate resources
- [ ] **SQL Injection**: Forms protected against SQL injection
- [ ] **XSS Protection**: Forms protected against cross-site scripting
- [ ] **CSRF Protection**: CSRF tokens working properly

## Maintenance Schedule

### Daily
- [ ] Monitor application logs for errors
- [ ] Check application availability and performance
- [ ] Verify backup completion

### Weekly
- [ ] Review security logs
- [ ] Check database performance metrics
- [ ] Update any critical security patches

### Monthly
- [ ] Review and rotate sensitive credentials
- [ ] Test backup restoration process
- [ ] Review resource usage and scaling needs
- [ ] Update dependencies (security patches)

## Emergency Procedures

### Rollback Plan
1. **Immediate**: Use Azure deployment slots to rollback quickly
2. **Database**: Have database backup restoration procedure ready
3. **Communications**: Prepare incident communication template

### Contact Information
- **Azure Support**: [Your Azure support plan details]
- **Development Team**: [Contact information]
- **Database Administrator**: [Contact information]

## Performance Benchmarks

Document your application's expected performance:

- **Homepage Load Time**: < 2 seconds
- **Admin Panel Load Time**: < 3 seconds
- **Database Query Response**: < 100ms average
- **Cache Hit Rate**: > 90%
- **Uptime Target**: 99.9%

## Cost Monitoring

- [ ] **Monthly Budget Alert**: Set up billing alerts
- [ ] **Resource Usage Review**: Regular review of resource consumption
- [ ] **Scaling Strategy**: Plan for traffic spikes and scaling down during low usage

---

## Quick Commands Reference

```bash
# Check application status
curl -I https://your-app.azurewebsites.net

# View application logs
az webapp log tail --resource-group pantracrm-rg --name pantracrm-app

# Scale application
az appservice plan update --resource-group pantracrm-rg --name pantracrm-plan --sku S1

# Check database connectivity
mysql -h your-mysql-server.mysql.database.azure.com -u adminuser -p

# Test Redis connectivity
redis-cli -h your-redis.redis.cache.windows.net -p 6380 -a your_redis_key --tls
```

---

**Last Updated**: [Current Date]  
**Environment**: Production  
**Application**: PantryCRM v1.0  
**Platform**: Azure App Service (Linux, PHP 8.3)