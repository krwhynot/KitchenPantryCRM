# PantryCRM

**A Laravel 12 + Filament 3.3 CRM Application for Food Service Businesses**

PantryCRM is a comprehensive Customer Relationship Management system designed specifically for food service businesses, distributors, and suppliers. Built with modern Laravel and Filament technologies, it provides intuitive management of organizations, contacts, interactions, and sales opportunities.

## Project Overview

### Technology Stack
- **Backend**: Laravel 12.x
- **Admin Panel**: Filament 3.3+
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Frontend**: Laravel Livewire + Alpine.js
- **Testing**: PHPUnit with comprehensive test coverage
- **Deployment**: Azure App Service ready

### Key Features
- 🏢 **Organization Management**: Restaurant and supplier tracking
- 👥 **Contact Management**: Individual contact relationships  
- 💬 **Interaction Tracking**: Communication history and follow-ups
- 💰 **Opportunity Pipeline**: Sales opportunity management
- 📊 **Reporting Dashboard**: Business intelligence and analytics
- 🔐 **Role-based Access**: Secure user management
- 📱 **Responsive Design**: Mobile-friendly interface

## Change Tracking System

This project implements a comprehensive change tracking system that logs all file modifications across the codebase. Each directory contains a README.md file with a standardized change log format.

### Change Log Format
```
YYYY-MM-DD | filename | change_type | description
```

### Change Types
- **ADDED**: New file created
- **MODIFIED**: Existing file updated
- **DELETED**: File removed
- **RENAMED**: File moved or renamed
- **BASELINE**: Initial state documentation
- **FIXED**: Bug fix or correction
- **REFACTORED**: Major structural changes

### Directory Coverage
The following directories have active change tracking:
- `.taskmaster/tasks/` - Project task management
- `tests/Feature/Smoke/` - Smoke testing suite  
- `app/Models/` - Eloquent models and relationships
- `database/migrations/` - Database schema changes
- `database/factories/` - Model factories for testing
- `app/Filament/Resources/` - Admin panel resources
- `app/Livewire/` - Interactive UI components
- `config/` - Application configuration files

### Using the Change Tracking System
1. **When modifying files**: Update the appropriate directory's README.md
2. **Follow the format**: Use the standardized YYYY-MM-DD | filename | type | description format
3. **Be descriptive**: Include meaningful descriptions of changes
4. **Update immediately**: Log changes when they happen, not later

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
