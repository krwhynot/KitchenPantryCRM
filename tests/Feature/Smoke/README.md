# Smoke Testing Suite for PantryCRM

This directory contains smoke tests designed to verify that the Laravel + Filament CRM application is properly installed and configured after initial setup.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | SmokeTestSuite.php | ADDED | Comprehensive suite runner and validation tests
- 2025-06-21 | SystemComponentsTest.php | ADDED | PHP environment and system requirements testing
- 2025-06-21 | DevelopmentServerTest.php | ADDED | Server configuration and routing tests  
- 2025-06-21 | FilamentInstallationTest.php | ADDED | Filament admin panel installation verification
- 2025-06-21 | DatabaseConnectivityTest.php | ADDED | SQLite database functionality verification
- 2025-06-21 | ApplicationHealthTest.php | ADDED | Core Laravel application health checks
- 2025-06-21 | README.md | ADDED | Comprehensive documentation and usage instructions

### Change Types Legend
- **ADDED**: New test file created
- **MODIFIED**: Existing test updated/enhanced
- **DELETED**: Test file removed
- **RENAMED**: Test moved or renamed
- **BASELINE**: Initial state documentation
- **FIXED**: Bug fix in test logic
- **REFACTORED**: Test structure improvements

### File Inventory
Current test files in this directory:
- ApplicationHealthTest.php (11 tests) - Laravel core functionality
- DatabaseConnectivityTest.php (12 tests) - Database operations and performance  
- FilamentInstallationTest.php (12 tests) - Filament setup verification
- DevelopmentServerTest.php (18 tests) - Server and routing configuration
- SystemComponentsTest.php (20 tests) - PHP environment requirements
- SmokeTestSuite.php (4 tests) - Meta-testing and suite validation
- README.md - Documentation and usage guide

## Purpose

Smoke tests are high-level tests that verify basic functionality and system health after installation. They are designed to quickly identify if the application is ready for development or deployment.

## Test Coverage

### 1. ApplicationHealthTest
- Application boots successfully
- Environment configuration is valid  
- Database connection works
- Core tables exist
- Artisan commands function
- Storage directories are writable
- Cache and session systems work
- Logging system is functional

### 2. DatabaseConnectivityTest  
- Database connection is active
- SQLite database configuration is correct
- Basic CRUD operations work
- Transactions function properly
- Eloquent models interact correctly
- Foreign key constraints are enforced
- Performance is acceptable

### 3. FilamentInstallationTest
- Filament package is properly installed
- Service providers are registered
- Admin panel routes are accessible
- Directory structure is correct
- Middleware is configured
- User authentication works

### 4. DevelopmentServerTest
- Application routes are registered
- Home and admin routes respond
- Middleware stack is functional
- Session handling works
- CSRF protection is active
- Error handling is configured
- Environment variables are loaded

### 5. SystemComponentsTest
- PHP version compatibility
- Required extensions are loaded
- Memory and execution limits are sufficient
- File upload settings are configured
- Cache, queue, and mail systems work
- Service container is functional
- Validation and templating engines work

## Running Smoke Tests

### Run All Smoke Tests
```bash
php artisan test tests/Feature/Smoke/
```

### Run Individual Test Suites
```bash
# Application health
php artisan test tests/Feature/Smoke/ApplicationHealthTest.php

# Database connectivity
php artisan test tests/Feature/Smoke/DatabaseConnectivityTest.php

# Filament installation
php artisan test tests/Feature/Smoke/FilamentInstallationTest.php

# Development server
php artisan test tests/Feature/Smoke/DevelopmentServerTest.php

# System components
php artisan test tests/Feature/Smoke/SystemComponentsTest.php
```

### Run Comprehensive Suite
```bash
php artisan test tests/Feature/Smoke/SmokeTestSuite.php
```

## When to Run Smoke Tests

1. **After Initial Installation**: Run immediately after Laravel + Filament setup
2. **Environment Changes**: After changing database, cache, or server configuration
3. **Deployment**: Before and after deploying to new environments
4. **Troubleshooting**: When basic functionality appears broken
5. **CI/CD Pipeline**: As part of automated testing workflow

## Expected Results

All smoke tests should pass in a properly configured environment. Failed tests indicate:

- **ApplicationHealthTest failures**: Basic Laravel installation issues
- **DatabaseConnectivityTest failures**: Database configuration problems
- **FilamentInstallationTest failures**: Filament setup issues
- **DevelopmentServerTest failures**: Server or routing configuration problems
- **SystemComponentsTest failures**: PHP or system requirement issues

## Troubleshooting Failed Tests

### Common Issues and Solutions

1. **Database Connection Failures**
   - Ensure SQLite database file exists at `database/database.sqlite`
   - Check database permissions
   - Run `php artisan migrate` if needed

2. **Environment Configuration Issues**
   - Verify `.env` file exists and is properly configured
   - Check `APP_KEY` is set (run `php artisan key:generate` if needed)
   - Ensure proper environment variables are loaded

3. **Permission Issues**
   - Verify `storage/` and `bootstrap/cache/` directories are writable
   - Check file permissions: `chmod -R 775 storage bootstrap/cache`

4. **Filament Issues**
   - Ensure Filament is properly installed via Composer
   - Check if admin routes are registered
   - Verify user model is configured

5. **PHP Configuration**
   - Check PHP version is 8.1 or higher
   - Ensure required extensions are installed
   - Verify memory_limit and max_execution_time settings

## Integration with Task Management

These smoke tests correspond to **Task 21** in the project task management system, verifying the Laravel/Filament installation and basic application functionality.

## Performance Considerations

Smoke tests are designed to run quickly (typically under 60 seconds total) to provide rapid feedback. They focus on:

- Critical path functionality
- Basic system health checks
- Minimal data setup and teardown
- Fast execution without deep integration testing

For comprehensive testing, see the full test suite in `tests/Unit/` and `tests/Feature/`.