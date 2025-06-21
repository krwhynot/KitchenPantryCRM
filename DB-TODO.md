# Database Migration Plan (Task 22)

## Step 1: Generate Migration Files

The `users` table migration already correctly uses UUIDs. No changes are needed there.

Please execute the following commands in your terminal to generate the migration files for the remaining tables.

```bash
php artisan make:migration create_organizations_table
php artisan make:migration create_contacts_table
php artisan make:migration create_interactions_table
php artisan make:migration create_opportunities_table
php artisan make:migration create_principals_table
php artisan make:migration create_product_lines_table
php artisan make:migration create_settings_table
```

## Step 2: Confirmation

After running these commands, please confirm that the files have been created successfully. I will then provide the code to populate the `up()` method for each new migration file.