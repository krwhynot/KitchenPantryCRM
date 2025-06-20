<img src="https://r2cdn.perplexity.ai/pplx-full-logo-primary-dark%402x.png" class="logo" width="120"/>

# Detailed Laravel Filament CRM Implementation Blueprint for Azure with PostgreSQL Support

## Phase 1: Environment Setup and Installation (Week 1)

### Setting Up Your Development Environment

Before diving into the implementation, ensure your local development environment meets the following requirements:

- PHP 8.1+ installed on your development machine [^1][^2]
- Composer package manager installed and updated to the latest version [^3]
- Git for version control [^3]
- A code editor like Visual Studio Code with PHP extensions [^4]


### Laravel Project Creation

Start by creating a fresh Laravel project using Composer:

```bash
composer create-project laravel/laravel FoodService-CRM
cd FoodService-CRM
```

This command creates a new Laravel project in a directory named "FoodService-CRM" and navigates into it [^3].

### Filament Installation

Install Filament using Composer and set up the admin panel:

```bash
composer require filament/filament:"^3.3" -W
php artisan filament:install --panels
```

These commands install Filament and create a new service provider at `app/Providers/Filament/AdminPanelProvider.php` [^1][^2].

### Verify Service Provider Registration

Check that the Filament service provider is properly registered:

- For Laravel 11+: Check `bootstrap/providers.php`
- For Laravel 10 and below: Check `config/app.php`

If the provider isn't registered, add it manually to ensure Filament works correctly [^1][^2].

### Create Admin User

Generate your first admin user with the following command:

```bash
php artisan make:filament-user
```

This will prompt you to enter your name, email, and password for the admin account [^1][^3].

## Phase 2: PostgreSQL Database Configuration (Week 1-2)

### Local PostgreSQL Setup

For local development with PostgreSQL compatibility:

1. Install PostgreSQL on your development machine [^5]
2. Create a new database for your CRM project [^5]
3. Update your `.env` file with PostgreSQL connection details:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=foodservice_crm
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

This configuration sets up Laravel to use PostgreSQL locally, ensuring compatibility with your future Azure deployment [^5].

### Database Migration Structure

Create migrations for your core CRM entities with PostgreSQL compatibility in mind:

```bash
php artisan make:migration create_organizations_table
php artisan make:migration create_contacts_table
php artisan make:migration create_interactions_table
php artisan make:migration create_opportunities_table
php artisan make:migration create_distributors_table
```

When defining your migrations, use PostgreSQL-compatible column types and constraints [^6]:

```php
// Example for organizations table
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('priority', ['A', 'B', 'C', 'D']);
    $table->string('market_segment');
    $table->jsonb('metadata')->nullable(); // PostgreSQL jsonb type for flexible data
    $table->timestamps();
    $table->softDeletes(); // For archiving instead of permanent deletion
});
```

The `jsonb` type is particularly useful in PostgreSQL for storing flexible metadata that can evolve with your business needs [^5][^6].

## Phase 3: Filament Resource Generation (Week 2-3)

### Creating Model Classes

Generate model classes for each entity in your CRM:

```bash
php artisan make:model Organization
php artisan make:model Contact
php artisan make:model Interaction
php artisan make:model Opportunity
php artisan make:model Distributor
```

Define relationships between models to reflect your CRM requirements [^7].

### Generating Filament Resources

Use Filament's resource generation with the `--generate` flag to automatically create resources based on your database schema:

```bash
php artisan make:filament-resource Organization --generate
php artisan make:filament-resource Contact --generate
php artisan make:filament-resource Interaction --generate
php artisan make:filament-resource Opportunity --generate
php artisan make:filament-resource Distributor --generate
```

The `--generate` flag analyzes your database schema and creates appropriate form fields and table columns automatically [^8][^9].

### Customizing Resource Forms and Tables

After generation, customize each resource to better fit your CRM requirements:

```php
// Example for OrganizationResource.php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('priority')
                ->options([
                    'A' => 'A - High Priority',
                    'B' => 'B - Medium Priority',
                    'C' => 'C - Normal Priority',
                    'D' => 'D - Low Priority',
                ])
                ->required(),
            Forms\Components\Select::make('market_segment')
                ->options([
                    'Fine Dining' => 'Fine Dining',
                    'Fast Food' => 'Fast Food',
                    'Healthcare' => 'Healthcare',
                    // Add more segments as needed
                ])
                ->required(),
            // Add more fields as needed
        ]);
}
```

This customization ensures your forms and tables reflect the specific needs of your food service CRM [^8][^9].

## Phase 4: Azure Infrastructure Setup (Week 3-4)

### Azure PostgreSQL Flexible Server Creation

Set up an Azure PostgreSQL Flexible Server through the Azure portal:

1. Sign in to the Azure portal
2. Search for "Azure Database for PostgreSQL"
3. Select "Azure Database for PostgreSQL - Flexible Servers"
4. Click "Create" to start the configuration process [^10][^11]

Configure the server with these settings:

- **Basics tab**:
    - Subscription: Select your Azure subscription
    - Resource group: Create a new one for your CRM project
    - Server name: Choose a unique name (e.g., foodservice-crm-db)
    - Region: Select a region close to your users
    - PostgreSQL version: Choose version 14 or higher
    - Workload type: Select "Development" for testing, "Production" for live deployment [^10][^11]
- **Compute + storage**:
    - Select appropriate compute tier based on expected usage
    - Configure storage size (start with 32GB for development)
    - Enable storage auto-growth to prevent running out of space [^11]
- **Authentication**:
    - Authentication method: PostgreSQL authentication only
    - Admin username: Create a secure admin username
    - Password: Set a strong password [^10][^11]
- **Networking**:
    - Allow access from Azure services
    - Add your development IP address to firewall rules [^11]


### Azure App Service Setup

Create an Azure App Service to host your Laravel Filament application:

1. In the Azure portal, search for "App Service"
2. Click "Create" to start the configuration
3. Configure the basic settings:
    - Subscription and Resource Group: Same as your PostgreSQL server
    - Name: Choose a unique name for your web app
    - Publish: Code
    - Runtime stack: PHP 8.1 or higher
    - Operating System: Linux
    - Region: Same as your PostgreSQL server
    - App Service Plan: Create a new plan or select an existing one [^12][^4]
4. Review and create the App Service [^4]

## Phase 5: Database Migration and Deployment (Week 4-5)

### Preparing for Azure Deployment

Create a deployment script to automate the deployment process:

```bash
# deployment.sh
#!/bin/bash

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set proper permissions
chmod -R 775 storage bootstrap/cache

# Generate application key if not already set
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Clear and cache configuration
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

This script handles the necessary steps for deploying your Laravel application to Azure [^4][^13].

### Setting Up Azure CLI for Deployment

Install and configure Azure CLI for streamlined deployment:

```bash
# Login to Azure
az login

# Set the subscription
az account set --subscription your-subscription-id

# Deploy to Azure App Service
az webapp up --name your-app-name --resource-group your-resource-group --location "Central US" --sku B1
```

These commands authenticate with Azure and deploy your application to the App Service [^4].

### Configuring Environment Variables in Azure

Set up environment variables in the Azure App Service:

1. Navigate to your App Service in the Azure portal
2. Go to Settings > Configuration > Application settings
3. Add the following key-value pairs:
    - `DB_CONNECTION`: pgsql
    - `DB_HOST`: Your PostgreSQL server hostname
    - `DB_PORT`: 5432
    - `DB_DATABASE`: Your database name
    - `DB_USERNAME`: Your database username
    - `DB_PASSWORD`: Your database password
    - `APP_KEY`: Your Laravel application key
    - `APP_ENV`: production
    - `APP_DEBUG`: false [^13][^14]

### Setting Up Startup Command

Add a startup command to ensure proper initialization of your Laravel application:

1. In the Azure portal, go to your App Service
2. Navigate to Settings > Configuration > General settings
3. In the Startup Command field, add:
```
php artisan optimize && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

This command ensures that your application properly loads environment variables from Azure App Service configuration [^15][^14].

## Phase 6: Advanced Filament Customization (Week 5-6)

### Creating Custom Dashboard Widgets

Develop custom dashboard widgets to display key CRM metrics:

```php
// app/Filament/Widgets/OrganizationStatsWidget.php
namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Organizations', Organization::count()),
            Stat::make('High Priority (A)', Organization::where('priority', 'A')->count()),
            Stat::make('Medium Priority (B)', Organization::where('priority', 'B')->count()),
        ];
    }
}
```

Register this widget in your AdminPanelProvider to display it on the dashboard [^9].

### Implementing Dropdown Management System

Create a flexible dropdown management system for market segments, distributor types, and other categorizations:

1. Create models and migrations for dropdown options:
```bash
php artisan make:model MarketSegment -m
php artisan make:model DistributorType -m
```

2. Generate Filament resources for these models:
```bash
php artisan make:filament-resource MarketSegment --generate
php artisan make:filament-resource DistributorType --generate
```

3. Update your Organization form to use these dynamic dropdowns:
```php
Forms\Components\Select::make('market_segment_id')
    ->relationship('marketSegment', 'name')
    ->searchable()
    ->preload()
```

This approach allows you to add new dropdown options without modifying code [^9][^7].

## Phase 7: Testing and Optimization (Week 6-7)

### Performance Testing

Test your application's performance with PostgreSQL on Azure:

1. Create test data using Laravel's factory system
2. Monitor query performance using Laravel Debugbar
3. Optimize slow queries with proper indexing [^5][^6]

### Security Hardening

Implement security best practices for your Azure-hosted application:

1. Enable HTTPS for all traffic
2. Configure proper CORS settings
3. Set up Azure Web Application Firewall
4. Implement proper authentication and authorization using Filament's built-in capabilities [^16]

### Backup and Disaster Recovery

Configure automated backups for your PostgreSQL database:

1. In the Azure portal, navigate to your PostgreSQL Flexible Server
2. Go to Management > Backups
3. Configure backup retention period (7-35 days)
4. Set up geo-redundant backup storage for production environments [^17]

## Phase 8: Final Deployment and Documentation (Week 7-8)

### Production Deployment Checklist

Before final deployment, ensure:

1. All environment variables are properly configured in Azure
2. Database migrations run successfully
3. All Filament resources and pages load correctly
4. File permissions are set correctly for storage and cache directories [^4][^13]

### User Documentation

Create comprehensive documentation for your CRM system:

1. Admin guide for managing dropdown options and system settings
2. User guide for sales team members
3. Technical documentation for future maintenance and enhancements [^7]

### Monitoring and Maintenance Plan

Set up monitoring for your Azure resources:

1. Configure Azure Monitor for your App Service and PostgreSQL server
2. Set up alerts for high resource usage or errors
3. Establish a regular maintenance schedule for updates and optimizations [^17]

This detailed blueprint provides a comprehensive roadmap for implementing your food service CRM using Laravel Filament with Azure hosting and PostgreSQL database support. By following these steps, you'll create a robust, expandable system that meets your current needs while providing flexibility for future growth.

<div style="text-align: center">⁂</div>

[^1]: https://filamentphp.com/docs/3.x/panels/installation/

[^2]: https://filamentphp.com/docs/4.x/introduction/installation/

[^3]: https://laraveldaily.com/lesson/filament-crm/install-laravel-filament

[^4]: https://faun.pub/how-to-deploy-your-php-stack-based-laravel-web-application-on-azure-app-service-from-vs-code-e5692531aecd

[^5]: https://laraveldaily.com/post/postgresql-laravel-what-you-need-to-know

[^6]: https://laravel.com/docs/12.x/migrations

[^7]: https://github.com/tqt97/laravel-crm-filament

[^8]: https://laraveldaily.com/tip/generate-filament-resource-from-existing-db-schema

[^9]: https://laraveldaily.com/post/filament-v3-nested-resources-trait-pages

[^10]: https://learn.microsoft.com/en-us/azure/postgresql/flexible-server/quickstart-create-server

[^11]: https://techdocs.genetec.com/r/en-US/GenetecTM-Data-Exporter-Plugin-Guide-1.0.0/Creating-an-Azure-PostgreSQL-flexible-server

[^12]: https://cypressnorth.com/web-programming-and-development/how-to-run-a-laravel-application-with-reverb-in-an-azure-web-app/

[^13]: https://learn.microsoft.com/en-us/azure/mysql/flexible-server/tutorial-php-database-app

[^14]: https://stackoverflow.com/questions/73576090/azure-web-service-env-variables-not-working-with-azure-container-registry-acr

[^15]: https://www.reddit.com/r/laravel/comments/x5lgw5/deploying_laravel_to_azure_web_services_for/

[^16]: https://ieeexplore.ieee.org/document/10912971/

[^17]: https://learn.microsoft.com/en-us/azure/postgresql/flexible-server/overview

[^18]: https://www.mdpi.com/2077-0383/9/8/2322

[^19]: https://rsdjournal.org/index.php/rsd/article/view/36234

[^20]: https://dev.to/nikkbh/how-to-create-a-postgresql-database-on-azure-2go6

[^21]: https://dev.to/snehalkadwe/filament-v3-with-laravel-10-3h9k

[^22]: https://community.octoprint.org/t/filament-manager-plugin-how-to-setup-postgresql-database/10698

[^23]: https://learn.microsoft.com/en-us/answers/questions/1484660/azure-database-for-postgres-and-laravel-applicatio

[^24]: https://www.youtube.com/watch?v=AsL7MI8b0m4

[^25]: https://csitjournal.khmnu.edu.ua/index.php/csit/article/view/214

[^26]: https://www.ijsr.net/getabstract.php?paperid=SR210611095250

[^27]: https://www.onlinescientificresearch.com/articles/composing-serverless-azure-functions-in-nodejs-with-mysql--database-integration.pdf

[^28]: http://dpi-journals.com/index.php/dtcse/article/view/17239

[^29]: https://www.mdpi.com/2543-6031/92/5/37

[^30]: https://ieeexplore.ieee.org/document/10847490/

[^31]: https://stackoverflow.com/questions/79223120/how-to-run-alembic-migrations-in-multiple-postgresql-database

[^32]: https://learn.microsoft.com/en-nz/answers/questions/2282837/deploy-laravel-project-with-azure-kudu

[^33]: https://link.springer.com/10.1007/s40891-022-00424-9

[^34]: https://journals.sagepub.com/doi/10.1177/0954406215620453

[^35]: https://direct.mit.edu/leon/article/48/5/499-500/46227

[^36]: https://www.semanticscholar.org/paper/2380a12fde9ed4e7cddf9f9e99c51f6cd5b643f3

[^37]: https://direct.mit.edu/leon/article/48/5/489/46220

[^38]: https://www.semanticscholar.org/paper/b9e066ea00478ab27954c5bb24afbe4c180e50e0

[^39]: https://direct.mit.edu/leon/article/48/5/502/46234

[^40]: https://filamentphp.com/docs/2.x/admin/installation

[^41]: https://www.youtube.com/watch?v=rN9XI9KCz0c

[^42]: https://stackoverflow.com/questions/73053717/login-issue-in-laravel-9-x-with-filament

[^43]: https://www.semanticscholar.org/paper/3add2b73c9372a2d20da2c7422701aab5934a59e

[^44]: https://www.semanticscholar.org/paper/6a90dd5a1f3d8b42a534b9b4f51d994948f7a432

[^45]: https://link.springer.com/10.1007/978-1-4842-7122-3_8

[^46]: https://www.semanticscholar.org/paper/69382ed8a3e1152740c03c437fd77a25ad6017d3

[^47]: https://learn.microsoft.com/en-us/answers/questions/2282837/deploy-laravel-project-with-azure-kudu

[^48]: https://stackoverflow.com/questions/69461833/how-to-deploy-an-app-service-in-azure-with-laravel-8-and-php-8-without-public-en

