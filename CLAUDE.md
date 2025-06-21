# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with Filament 3.3 admin panel, designed as a CRM system for food service businesses. The application manages Organizations, Contacts, Interactions, Opportunities, and Distributors with a focus on Azure deployment and SQLite database.

## Development Practices

### File Change Logging
- For any changes made to a file in a folder, log it with date, file name, and kind of change in a readme file within the same folder

## Troubleshooting

For common issues and their solutions, refer to:
- `TROUBLESHOOTING.md` - Quick reference guide
- `docs/troubleshooting/` - Detailed solution documentation

### Known Issues & Solutions
- **SQLite Session "payload" Column Error**: See [docs/troubleshooting/sqlite-session-fix.md](docs/troubleshooting/sqlite-session-fix.md)
- **Database Connection Issues**: Ensure SQLite file exists at `database/database.sqlite`
- **Cache Problems**: Use `php artisan optimize:clear` to clear all caches

### Important Commands
```bash
# Start development server
php artisan serve

# Database operations
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear

# Generate session table (if needed)
php artisan make:session-table
```

[... rest of the existing content remains the same ...]