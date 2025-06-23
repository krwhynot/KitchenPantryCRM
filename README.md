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

## 🚀 Azure Deployment

PantryCRM is optimized for deployment on Azure App Service. We provide comprehensive deployment guides and automation scripts.

### Quick Deployment

1. **Automated Deployment Script**:
   ```bash
   ./deploy-to-azure.sh
   ```

2. **Manual Azure CLI Deployment**:
   See [AZURE_DEPLOYMENT_GUIDE.md](AZURE_DEPLOYMENT_GUIDE.md) for detailed instructions.

3. **GitHub Actions CI/CD**:
   Automated deployment pipeline configured in `.github/workflows/azure-deploy.yml`.

### Deployment Resources

- 📖 **[Azure Deployment Guide](AZURE_DEPLOYMENT_GUIDE.md)** - Complete step-by-step deployment instructions
- ✅ **[Production Checklist](PRODUCTION_CHECKLIST.md)** - Pre and post-deployment verification steps
- 🔧 **[Troubleshooting Guide](TROUBLESHOOTING.md)** - Common deployment issues and solutions

### Azure Service Recommendations

| Component | Recommended Service | Monthly Cost |
|-----------|-------------------|--------------|
| **Web App** | App Service (Linux, PHP 8.3) | ~$13-73 |
| **Database** | Azure Database for MySQL Flexible Server | ~$20-60 |
| **Cache** | Azure Cache for Redis | ~$15-75 |
| **Storage** | Azure Blob Storage (optional) | ~$5-20 |

### Environment Variables for Production

Key environment variables for Azure deployment:

```bash
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-mysql-server.mysql.database.azure.com
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=your-redis.redis.cache.windows.net
FILAMENT_DOMAIN=your-app.azurewebsites.net
```

See [.env.example](.env.example) for complete configuration options.

## 🤝 Contributing

We welcome contributions to PantryCRM! Please read our contributing guidelines and code of conduct before submitting pull requests.

### Development Workflow

1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Run the test suite: `php artisan test`
5. Submit a pull request

## 🔒 Security

If you discover any security vulnerabilities, please send an email to [security@pantracrm.com](mailto:security@pantracrm.com). All security vulnerabilities will be promptly addressed.

## 📄 License

PantryCRM is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
