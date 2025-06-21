# Configuration Directory

## Directory Purpose
Contains Laravel configuration files that define application settings, service connections, and environment-specific parameters for the PantryCRM application.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | README.md | ADDED | Documentation and change tracking for configuration files
- 2025-06-20 | [All config files] | BASELINE | Initial Laravel configuration setup (pre-existing)

### Change Types Legend
- **ADDED**: New configuration file created
- **MODIFIED**: Existing configuration updated
- **DELETED**: Configuration file removed
- **RENAMED**: Configuration file renamed
- **BASELINE**: Initial state documentation
- **SECURITY**: Security-related configuration changes
- **PERFORMANCE**: Performance optimization settings
- **FEATURE**: New feature configuration
- **ENVIRONMENT**: Environment-specific changes

### File Inventory
Current configuration files in this directory:
- app.php - Core application settings and service providers (pre-existing)
- auth.php - Authentication guards, providers, and password settings (pre-existing)
- cache.php - Caching system configuration and stores (pre-existing)
- database.php - Database connections and SQLite settings (pre-existing)
- filesystems.php - File storage and disk configurations (pre-existing)
- logging.php - Logging channels and handlers (pre-existing)
- mail.php - Email service and SMTP configurations (pre-existing)
- queue.php - Queue drivers and connection settings (pre-existing)
- services.php - Third-party service API configurations (pre-existing)
- session.php - Session storage and security settings (pre-existing)
- telescope.php - Laravel Telescope debugging configuration (pre-existing)

## Configuration Overview

### Core Application Settings

#### app.php
**Purpose**: Fundamental application configuration
**Key Settings**:
- Application name, environment, and debug mode
- Application URL and timezone settings
- Encryption key and cipher configuration
- Service provider registrations
- Class alias definitions

**Critical Values**:
```php
'name' => env('APP_NAME', 'PantryCRM'),
'env' => env('APP_ENV', 'production'),
'debug' => (bool) env('APP_DEBUG', false),
'url' => env('APP_URL', 'http://localhost'),
'timezone' => 'UTC',
```

#### database.php
**Purpose**: Database connection configuration
**Key Settings**:
- SQLite database configuration for development
- Connection parameters and options
- Migration and seeding settings

**Current Setup**:
```php
'default' => env('DB_CONNECTION', 'sqlite'),
'connections' => [
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => env('DB_DATABASE', database_path('database.sqlite')),
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    ],
],
```

### Authentication & Security

#### auth.php
**Purpose**: Authentication system configuration
**Key Settings**:
- User guards and providers
- Password reset configuration
- Authentication defaults

**Configuration**:
```php
'defaults' => [
    'guard' => 'web',
    'passwords' => 'users',
],
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

#### session.php
**Purpose**: Session management configuration
**Key Settings**:
- Session driver and lifetime
- Security settings (httponly, secure, same_site)
- Session storage configuration

**Security Settings**:
```php
'lifetime' => 120,
'secure' => env('SESSION_SECURE_COOKIE', false),
'http_only' => true,
'same_site' => 'lax',
```

### Performance & Caching

#### cache.php
**Purpose**: Caching system configuration
**Key Settings**:
- Cache drivers and stores
- Redis/Memcached configurations
- Cache prefixes and serialization

**Default Configuration**:
```php
'default' => env('CACHE_DRIVER', 'file'),
'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
],
```

#### queue.php
**Purpose**: Queue system configuration
**Key Settings**:
- Queue drivers and connections
- Job retry and timeout settings
- Failed job configuration

### Communication Services

#### mail.php
**Purpose**: Email service configuration
**Key Settings**:
- SMTP server settings
- Mail driver configuration
- Global from address

**Configuration Structure**:
```php
'default' => env('MAIL_MAILER', 'smtp'),
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        'port' => env('MAIL_PORT', 587),
    ],
],
```

#### logging.php
**Purpose**: Application logging configuration
**Key Settings**:
- Log channels and handlers
- Log levels and formatting
- File rotation and storage

### Development Tools

#### telescope.php
**Purpose**: Laravel Telescope debugging configuration
**Key Settings**:
- Telescope enablement and middleware
- Data recording watchers
- Performance monitoring settings

## Environment-Specific Configurations

### Development Settings
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Production Settings
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Testing Settings
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
SESSION_DRIVER=array
```

## Food Service CRM Customizations

### Application-Specific Settings
While most configurations use Laravel defaults, some are tailored for CRM usage:

#### Session Configuration
- Extended session lifetime for long CRM work sessions
- Secure cookie settings for customer data protection
- Appropriate session cleanup intervals

#### Database Optimization
- Foreign key constraint enforcement for data integrity
- SQLite optimization for development simplicity
- Connection pooling settings for production

#### Caching Strategy
- Model caching for frequently accessed CRM data
- Query result caching for reporting features
- Asset caching for UI performance

#### Logging Configuration
- Enhanced logging for CRM activity tracking
- Customer interaction audit trails
- Performance monitoring for user experience

## Security Considerations

### Data Protection
- **Encryption**: All sensitive data encrypted at rest
- **Session Security**: Secure session handling configuration
- **CSRF Protection**: Cross-site request forgery prevention
- **Input Validation**: Configuration-level input sanitization

### Access Control
- **Authentication**: Secure user authentication configuration
- **Authorization**: Role-based access control settings
- **Password Security**: Strong password requirements
- **Session Management**: Secure session lifecycle handling

### Compliance
- **Data Retention**: Configurable data retention policies
- **Audit Logging**: Comprehensive activity logging
- **Privacy Controls**: Customer data privacy settings
- **Backup Configuration**: Secure backup and recovery settings

## Development Guidelines

### Configuration Best Practices
1. **Environment Variables**: Use .env for environment-specific settings
2. **Default Values**: Provide sensible defaults in config files
3. **Documentation**: Comment complex configuration options
4. **Security**: Never commit sensitive values to version control
5. **Validation**: Validate configuration values where possible

### Adding New Configurations
```php
// config/custom.php
return [
    'feature_enabled' => env('CUSTOM_FEATURE', false),
    'api_settings' => [
        'timeout' => env('API_TIMEOUT', 30),
        'retries' => env('API_RETRIES', 3),
    ],
];
```

### Accessing Configuration
```php
// In application code
$appName = config('app.name');
$dbConnection = config('database.default');
$customSetting = config('custom.feature_enabled', false);
```

## Maintenance & Updates

### Regular Maintenance
- **Review settings**: Periodically review all configuration values
- **Security updates**: Update security-related configurations
- **Performance tuning**: Optimize performance settings based on usage
- **Environment sync**: Ensure consistency across environments

### Configuration Validation
```php
// Validate critical configurations
if (!config('app.key')) {
    throw new Exception('Application key not set');
}

if (config('app.debug') && config('app.env') === 'production') {
    throw new Exception('Debug mode enabled in production');
}
```

### Backup Considerations
- **Configuration backup**: Include config files in backup strategy
- **Environment files**: Securely backup .env files
- **Version control**: Track configuration changes
- **Deployment**: Automate configuration deployment

## Troubleshooting

### Common Issues
1. **Cache problems**: Clear configuration cache with `php artisan config:clear`
2. **Environment variables**: Verify .env file exists and is readable
3. **Permission issues**: Check file permissions on storage directories
4. **Service connections**: Test database, cache, and queue connections
5. **Security settings**: Verify HTTPS and session security in production

### Debugging Configuration
```php
// Debug configuration values
php artisan tinker
>>> config('app.name')
>>> config('database.connections.sqlite')

// Clear configuration cache
php artisan config:clear

// Cache configurations for production
php artisan config:cache
```

### Performance Monitoring
- **Configuration caching**: Use `php artisan config:cache` in production
- **Environment optimization**: Minimize .env file parsing in production
- **Service monitoring**: Monitor external service connections
- **Resource usage**: Track memory and CPU usage patterns

## Azure Deployment Configuration

### Azure-Specific Settings
For Azure App Service deployment:
- **Database**: Azure Database for MySQL/PostgreSQL or SQLite
- **Storage**: Azure Blob Storage for file uploads
- **Caching**: Azure Redis Cache for session and data caching
- **Logging**: Azure Application Insights integration
- **Security**: Azure Key Vault for secrets management