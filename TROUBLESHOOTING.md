# PantryCRM Troubleshooting Guide

This document contains solutions to common issues encountered in the PantryCRM Laravel application.

## Quick Reference

| Issue | Solution Link | Status |
|-------|---------------|--------|
| SQLite Session "payload" Column Error | [docs/troubleshooting/sqlite-session-fix.md](docs/troubleshooting/sqlite-session-fix.md) | ✅ Resolved |

## Common Issues

### 1. Database & Sessions

#### SQLite Session Storage Error
**Error**: `SQLSTATE[HY000]: General error: 1 no such column: payload`

**Quick Fix**:
```bash
# Create fix migration
php artisan make:migration fix_sessions_table_for_laravel_sessions --table=sessions

# Add required columns in migration:
# - payload (longText)
# - last_activity (integer) 
# - ip_address (string, nullable)
# - user_agent (text, nullable)

# Run migration
php artisan migrate

# Clear caches
php artisan config:clear && php artisan cache:clear
```

**Detailed Solution**: See [SQLite Session Fix Guide](docs/troubleshooting/sqlite-session-fix.md)

### 2. Development Server

#### Server Won't Start
```bash
# Check if port is in use
php artisan serve --port=8001

# Or kill existing processes
pkill -f "php artisan serve"
```

#### Database File Missing
```bash
# Create SQLite database file
touch database/database.sqlite

# Run migrations
php artisan migrate --seed
```

### 3. Filament Admin Panel

#### Admin Panel Not Accessible
1. Verify server is running: `http://127.0.0.1:8000`
2. Check admin URL: `http://127.0.0.1:8000/admin`
3. Ensure user exists for login
4. Clear caches if needed

#### Authentication Issues
```bash
# Create admin user
php artisan make:filament-user

# Or check existing users
php artisan tinker --execute="User::all()->pluck('email')"
```

### 4. Environment Configuration

#### Cache Issues After Config Changes
```bash
# Clear all caches
php artisan optimize:clear

# Or individual caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

#### Environment File Problems
1. Copy `.env.example` to `.env`
2. Generate app key: `php artisan key:generate`
3. Set correct database path for SQLite

## Diagnostic Commands

### Database Diagnostics
```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo()"

# List all tables
php artisan tinker --execute="Schema::getTableListing()"

# Check specific table structure
php artisan tinker --execute="Schema::getColumnListing('sessions')"

# Test session functionality
php artisan tinker --execute="session(['test' => 'value']); echo session('test')"
```

### Application Health
```bash
# Check Laravel installation
php artisan about

# Verify routes
php artisan route:list

# Check migration status
php artisan migrate:status

# Test HTTP response
curl -I http://127.0.0.1:8000
```

### Log Monitoring
```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Check specific error patterns
grep -i "error\|exception\|failed" storage/logs/laravel.log
```

## Getting Help

### Internal Resources
1. Check `CLAUDE.md` for project-specific guidance
2. Review `docs/` directory for detailed documentation
3. Examine recent migration files for schema changes

### External Resources
1. [Laravel 12 Documentation](https://laravel.com/docs/12.x)
2. [Filament 3 Documentation](https://filamentphp.com/docs/3.x)
3. [SQLite Documentation](https://www.sqlite.org/docs.html)

### Reporting Issues
When reporting issues, include:
- Error message (full stack trace)
- Steps to reproduce
- Environment details (`php artisan about`)
- Recent changes made
- Log file excerpts

---

**Last Updated**: 2025-06-21  
**Laravel Version**: 12.19.3  
**Maintainer**: PantryCRM Development Team