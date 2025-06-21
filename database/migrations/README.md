# Database Migrations Directory

## Directory Purpose
Contains Laravel migration files that define the database schema structure for the PantryCRM application, including tables for organizations, contacts, interactions, opportunities, and food service principals.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | 2025_06_21_041314_create_product_lines_table.php | ADDED | Product lines table for principal product categories
- 2025-06-21 | 2025_06_21_041235_create_principals_table.php | ADDED | Principals table for food service suppliers
- 2025-06-21 | 2025_06_21_010002_fix_sessions_table_for_laravel_sessions.php | BASELINE | Session table fix (pre-existing)
- 2025-06-20 | [Multiple files] | BASELINE | Initial schema migrations (pre-existing)

### Change Types Legend
- **ADDED**: New migration file created
- **MODIFIED**: Existing migration updated (use carefully)
- **DELETED**: Migration file removed
- **RENAMED**: Migration file renamed
- **BASELINE**: Initial state documentation
- **ROLLBACK**: Migration rolled back
- **SCHEMA_CHANGE**: Database structure modification

### File Inventory
Current migration files in this directory (chronological order):
- 2025_06_20_194448_create_users_table.php - User authentication and management
- 2025_06_20_194449_create_organizations_table.php - Companies and restaurants
- 2025_06_20_194938_create_contacts_table.php - Individual contacts at organizations  
- 2025_06_20_195128_create_interactions_table.php - Customer communications and meetings
- 2025_06_20_195256_create_opportunities_table.php - Sales opportunities and deals
- 2025_06_20_195410_create_leads_table.php - Potential customer leads
- 2025_06_20_195527_create_contracts_table.php - Signed agreements and contracts
- 2025_06_20_195700_create_accounts_table.php - Financial account management
- 2025_06_20_195702_create_sessions_table.php - User session storage
- 2025_06_20_195705_create_verification_tokens_table.php - Email verification
- 2025_06_20_195706_create_system_settings_table.php - Application configuration
- 2025_06_21_010002_fix_sessions_table_for_laravel_sessions.php - Session table compatibility
- 2025_06_21_041235_create_principals_table.php - Food service principals/suppliers 🆕
- 2025_06_21_041314_create_product_lines_table.php - Principal product lines 🆕

## Database Schema Overview

### Core CRM Tables

#### Organizations (Companies/Restaurants)
- **Primary Key**: UUID
- **Key Fields**: name, type, address, phone, email, website, priority
- **Relationships**: hasMany contacts, interactions, opportunities

#### Contacts (Individual People)
- **Primary Key**: UUID  
- **Key Fields**: firstName, lastName, email, phone, position, isPrimary
- **Foreign Keys**: organization_id
- **Relationships**: belongsTo organization, hasMany interactions

#### Interactions (Communications)
- **Primary Key**: UUID
- **Key Fields**: type, subject, description, date, duration, outcome
- **Foreign Keys**: organization_id, contact_id
- **Relationships**: belongsTo organization, contact

#### Opportunities (Sales Deals)
- **Primary Key**: UUID
- **Key Fields**: name, value, stage, probability, expectedCloseDate
- **Foreign Keys**: organization_id, contact_id
- **Relationships**: belongsTo organization, contact

### Food Service Specific Tables

#### Principals (Suppliers)
- **Primary Key**: Auto-increment ID
- **Key Fields**: name, contact_name, email, phone, address, website
- **Relationships**: hasMany productLines

#### Product Lines (Product Categories)
- **Primary Key**: Auto-increment ID
- **Key Fields**: name, description, is_active
- **Foreign Keys**: principal_id (with cascade delete)
- **Relationships**: belongsTo principal

### System Tables

#### Users (Authentication)
- **Primary Key**: UUID
- **Key Fields**: name, email, password, email_verified_at
- **Relationships**: hasMany interactions, opportunities

#### Sessions (User Sessions)
- **Primary Key**: String ID
- **Key Fields**: user_id, ip_address, user_agent, payload, last_activity
- **Purpose**: Laravel session storage

## Migration Best Practices

### Creating New Migrations
```bash
# Create new migration
php artisan make:migration create_table_name_table

# Create migration with model
php artisan make:model ModelName -m

# Create migration for table modification
php artisan make:migration add_column_to_table_name_table
```

### Migration Guidelines
1. **Never modify existing migrations** that have been committed
2. **Use descriptive names** that clearly indicate the purpose
3. **Include both up() and down() methods** for rollback support
4. **Add foreign key constraints** with appropriate cascade actions
5. **Include indexes** for frequently queried columns
6. **Use proper data types** (UUID for primary keys, appropriate field lengths)

### Foreign Key Relationships
Current foreign key constraints:
- contacts.organization_id → organizations.id (CASCADE DELETE)
- interactions.organization_id → organizations.id (CASCADE DELETE)  
- interactions.contact_id → contacts.id (NULL ON DELETE)
- opportunities.organization_id → organizations.id (CASCADE DELETE)
- opportunities.contact_id → contacts.id (NULL ON DELETE)
- product_lines.principal_id → principals.id (CASCADE DELETE)

### Data Types and Conventions
- **Primary Keys**: UUID for core entities, auto-increment for lookup tables
- **Foreign Keys**: Match the referenced table's primary key type
- **Timestamps**: Use Laravel's timestamps() for created_at/updated_at
- **Text Fields**: Use appropriate lengths (string for short text, text for long)
- **Enums**: Define allowed values in migration comments

## Running Migrations

### Development Commands
```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration batch
php artisan migrate:rollback

# Rollback specific number of migration batches
php artisan migrate:rollback --step=3

# Reset and re-run all migrations (DESTRUCTIVE)
php artisan migrate:fresh

# Reset, re-run, and seed database
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Production Considerations
- **Always backup** database before running migrations in production
- **Test migrations** in staging environment first
- **Plan rollback strategy** for complex schema changes
- **Monitor performance** for migrations affecting large tables
- **Use maintenance mode** during critical schema changes

## Schema Validation

### Verification Steps
1. **Migration runs successfully**: `php artisan migrate`
2. **All tables created**: Verify in database client
3. **Foreign keys work**: Test cascade deletes and nulls
4. **Indexes exist**: Check query performance
5. **Models work**: Test Eloquent relationships
6. **Seeds run**: `php artisan db:seed`

### Testing Database Schema
- ✅ **Unit Tests**: Model relationship testing
- ✅ **Integration Tests**: Cross-table functionality
- ✅ **Performance Tests**: Query optimization validation
- ✅ **Constraint Tests**: Foreign key and validation testing

## Troubleshooting

### Common Issues
1. **Foreign key constraint errors**: Check referenced table exists first
2. **Column already exists**: Use `Schema::hasColumn()` checks
3. **Migration order**: Ensure dependencies run in correct sequence
4. **SQLite limitations**: Some features not supported in SQLite
5. **Data type mismatches**: Ensure consistent types across relationships

### Recovery Procedures
- **Failed migration**: Fix issue and re-run
- **Corrupt migration state**: Reset migration table if necessary
- **Data loss**: Restore from backup and replay migrations
- **Schema conflicts**: Resolve manually or create corrective migration