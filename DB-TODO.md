### Prompt for AI Code Assistant

**Objective:**

You are an expert Laravel developer. Your task is to build the complete database layer for a new CRM application. This involves creating all necessary migrations, Eloquent models with correct relationships, and seeders to populate the database with sample data.

**Core Rules & Conventions (MUST be followed):**

1.  **Primary Keys:** All database table primary keys **MUST** be UUIDs.
    *   **Migration:** Use `$table->uuid('id')->primary();`
    *   **Model:** Use the `Illuminate\Database\Eloquent\Concerns\HasUuids;` trait.

2.  **Foreign Keys & Columns:** All foreign key columns and date columns **MUST** use `snake_case`. Foreign keys should be formed by appending `_id` to the singular name of the related table (e.g., `organization_id`, `user_id`).
    *   **Migration:** Use `$table->foreignUuid('related_table_id')->constrained('related_tables');`

3.  **Eloquent Relationships:** All `belongsTo` and `hasMany` relationships defined in Eloquent models **MUST** explicitly specify the correct `snake_case` foreign key name as the second argument.
    *   **`belongsTo` Example:** `return $this->belongsTo(Organization::class, 'organization_id');`
    *   **`hasMany` Example:** `return $this->hasMany(Contact::class, 'organization_id');`

**Step-by-Step TODO List:**

Follow these steps in order to build the database layer.

---

### **Part 1: Foundational Tables (Users & Organizations)**

#### **1.1. Users Table**

*   **Migration:** Create the `users` table.
    ```bash
    php artisan make:migration create_users_table
    ```
    Update the `up()` method in the generated migration file:
    ```php
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
    ```

*   **Model:** Ensure the `app/Models/User.php` model uses the `HasUuids` trait and defines its relationships correctly.
    ```php
    <?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;

    class User extends Authenticatable
    {
        use HasFactory, Notifiable, HasUuids;

        protected $fillable = ['name', 'email', 'password'];
        protected $hidden = ['password', 'remember_token'];

        protected function casts(): array
        {
            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
            ];
        }

        public function accounts(): HasMany { return $this->hasMany(Account::class, 'user_id'); }
        public function sessions(): HasMany { return $this->hasMany(Session::class, 'user_id'); }
        public function assignedLeads(): HasMany { return $this->hasMany(Lead::class, 'assigned_to_id'); }
    }
    ```

#### **1.2. Organizations Table**

*   **Migration:** Create the `organizations` table.
    ```bash
    php artisan make:migration create_organizations_table
    ```
    Update the `up()` method:
    ```php
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }
    ```

*   **Model & Seeder:** Create the `Organization` model and seeder.
    ```bash
    php artisan make:model Organization -s
    ```
    Update `app/Models/Organization.php`:
    ```php
    <?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\HasMany;

    class Organization extends Model
    {
        use HasFactory, HasUuids;
        protected $guarded = [];

        public function contacts(): HasMany { return $this->hasMany(Contact::class, 'organization_id'); }
        public function interactions(): HasMany { return $this->hasMany(Interaction::class, 'organization_id'); }
        // ... other relationships
    }
    ```
    Update `database/seeders/OrganizationSeeder.php`:
    ```php
    public function run(): void
    {
        Organization::create(['name' => 'Global Foods Inc.', 'industry' => 'Food Service']);
        Organization::create(['name' => 'Local Catering Co.', 'industry' => 'Hospitality']);
        Organization::create(['name' => 'Farm Fresh Produce', 'industry' => 'Agriculture']);
    }
    ```

---

### **Part 2: Core CRM Tables**

For each of the following entities (`Contact`, `Interaction`, `Opportunity`, [Lead](cci:2://file:///r:/Projects/PantryCRM/database/seeders/LeadSeeder.php:10:0-67:1), `Contract`), perform these three steps:
1.  Create the migration and define the schema in the `up()` method.
2.  Create the model and define its relationships.
3.  Create the seeder and define its [run()](cci:1://file:///r:/Projects/PantryCRM/database/seeders/LeadSeeder.php:12:4-66:5) method.

#### **2.1. Contacts**
*   **Migration:** `...create_contacts_table.php`
    ```php
    Schema::create('contacts', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
        $table->string('firstName');
        $table->string('lastName');
        $table->string('position')->nullable();
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->boolean('isPrimary')->default(false);
        $table->timestamps();
    });
    ```
*   **Model:** `app/Models/Contact.php`
    ```php
    // ...
    use HasFactory, HasUuids;
    protected $guarded = [];
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class, 'organization_id'); }
    // ...
    ```
*   **Seeder:** `database/seeders/ContactSeeder.php`
    ```php
    // Logic to create contacts and link them to random organizations.
    ```

#### **2.2. Interactions**
*   **Migration:** `...create_interactions_table.php`
    ```php
    Schema::create('interactions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
        $table->foreignUuid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
        $table->enum('type', ['CALL', 'EMAIL', 'MEETING', 'VISIT']);
        $table->string('subject');
        $table->text('description')->nullable();
        $table->timestamp('date');
        $table->timestamps();
    });
    ```
*   **Model:** `app/Models/Interaction.php`
    ```php
    // ...
    use HasFactory, HasUuids;
    protected $guarded = [];
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class, 'organization_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class, 'contact_id'); }
    // ...
    ```
*   **Seeder:** `database/seeders/InteractionSeeder.php`
    ```php
    // Logic to create interactions linked to contacts and organizations.
    ```

*(Continue this pattern for `Opportunity`, [Lead](cci:2://file:///r:/Projects/PantryCRM/database/seeders/LeadSeeder.php:10:0-67:1), and `Contract`, ensuring all foreign keys and date columns are `snake_case` in the migrations and models.)*

---

### **Part 3: System & Auth Tables**

Create the migrations for the remaining tables: `accounts`, `sessions`, `verification_tokens`, and `system_settings`. Ensure their foreign keys (`user_id`) are correct.

#### **3.1. Accounts Table**
*   **Migration:** `...create_accounts_table.php`
    ```php
    Schema::create('accounts', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('provider');
        // ... other columns
        $table->timestamps();
    });
    ```

#### **3.2. Sessions Table**
*   **Migration:** `...create_sessions_table.php`
    ```php
    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
        // ... other columns
        $table->timestamps();
    });
    ```

---

### **Part 4: Finalizing the Seeding Process**

1.  **Update DatabaseSeeder:** Open `database/seeders/DatabaseSeeder.php` and update the [run](cci:1://file:///r:/Projects/PantryCRM/database/seeders/LeadSeeder.php:12:4-66:5) method to call all the new seeders in the correct order of dependency.

    ```php
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            ContactSeeder::class,
            InteractionSeeder::class,
            OpportunitySeeder::class,
            LeadSeeder::class,
            ContractSeeder::class,
        ]);
    }
    ```

2.  **Execute:** Run the final command from your terminal to drop all tables, run all migrations, and execute all seeders.

    ```bash
    php artisan migrate:fresh --seed
    ```

This will result in a fully built, correctly structured, and populated database ready for application development.