# Database Factories Directory

## Directory Purpose
Contains Laravel model factories that generate realistic test data for the PantryCRM application models. These factories are essential for testing, seeding, and development workflows.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | PrincipalFactory.php | ADDED | Factory for food service principals with realistic supplier data
- 2025-06-21 | ProductLineFactory.php | ADDED | Factory for principal product lines with food service categories
- 2025-06-21 | InteractionFactory.php | MODIFIED | Fixed schema mismatch - updated to use enum values and proper field names
- 2025-06-21 | ContactFactory.php | MODIFIED | Fixed schema mismatch - changed to firstName/lastName from first_name/last_name
- 2025-06-21 | OrganizationFactory.php | BASELINE | Organization factory (pre-existing)
- 2025-06-21 | OpportunityFactory.php | BASELINE | Opportunity factory (pre-existing)
- 2025-06-21 | UserFactory.php | BASELINE | User factory (pre-existing)
- 2025-06-21 | SystemSettingFactory.php | BASELINE | System settings factory (pre-existing)

### Change Types Legend
- **ADDED**: New factory file created
- **MODIFIED**: Existing factory updated/fixed
- **DELETED**: Factory file removed
- **RENAMED**: Factory moved or renamed
- **BASELINE**: Initial state documentation
- **SCHEMA_FIX**: Fixed to match database schema
- **ENHANCED**: Added new attributes or methods

### File Inventory
Current factory files in this directory:
- ContactFactory.php - Individual contact generation 🔧 FIXED
- InteractionFactory.php - Customer interaction generation 🔧 FIXED
- OpportunityFactory.php - Sales opportunity generation (pre-existing)
- OrganizationFactory.php - Company/restaurant generation (pre-existing)
- PrincipalFactory.php - Food service principal generation 🆕 NEW
- ProductLineFactory.php - Principal product line generation 🆕 NEW
- SystemSettingFactory.php - Application setting generation (pre-existing)
- UserFactory.php - User account generation (pre-existing)

## Factory Implementation Details

### Recently Fixed Factories

#### ContactFactory.php
**Schema Issues Fixed:**
- Changed `first_name`/`last_name` to `firstName`/`lastName` to match database schema
- Maintained realistic contact data generation for food service industry
- Kept organization relationship and position assignments

#### InteractionFactory.php  
**Schema Issues Fixed:**
- Updated to use proper enum values: 'CALL', 'EMAIL', 'MEETING', 'VISIT'
- Changed field mapping to match database schema
- Fixed outcome enum values: 'POSITIVE', 'NEUTRAL', 'NEGATIVE', 'FOLLOWUPNEEDED'
- Updated to use proper timestamp format for date field

### New Factory Implementations

#### PrincipalFactory.php
**Features:**
- Generates realistic food service supplier names
- Creates contact information for principal representatives
- Includes website and address data
- Supports relationship with ProductLine factory

**Sample Data Generated:**
```php
[
    'name' => 'Sysco Food Services',
    'contact_name' => 'John Smith',
    'email' => 'john.smith@sysco.com',
    'phone' => '555-0123',
    'address' => '123 Distribution Way, Houston, TX',
    'website' => 'https://sysco.com',
    'notes' => 'Leading food service distributor...'
]
```

#### ProductLineFactory.php
**Features:**
- Generates food service product categories
- Links to principal suppliers via foreign key
- Includes activation status and descriptions
- Food industry-specific product line names

**Sample Data Generated:**
```php
[
    'principal_id' => Principal::factory(),
    'name' => 'Fresh Produce',
    'description' => 'Farm-fresh fruits and vegetables...',
    'is_active' => true
]
```

## Factory Usage Patterns

### Basic Factory Usage
```php
// Create single instance
$contact = Contact::factory()->create();

// Create multiple instances
$contacts = Contact::factory()->count(10)->create();

// Create with specific attributes
$contact = Contact::factory()->create([
    'email' => 'specific@email.com'
]);
```

### Relationship Factories
```php
// Create organization with contacts
$organization = Organization::factory()
    ->has(Contact::factory()->count(3))
    ->create();

// Create principal with product lines
$principal = Principal::factory()
    ->has(ProductLine::factory()->count(5))
    ->create();

// Create interaction with relationships
$interaction = Interaction::factory()
    ->for(Organization::factory())
    ->for(Contact::factory())
    ->create();
```

### Testing Usage
```php
// In test files
public function test_contact_has_full_name()
{
    $contact = Contact::factory()->create([
        'firstName' => 'John',
        'lastName' => 'Doe'
    ]);
    
    $this->assertEquals('John Doe', $contact->full_name);
}
```

## Data Generation Strategies

### Realistic Food Service Data
Factories generate industry-appropriate data:

#### Organizations
- Restaurant names and types
- Food service segments (QSR, Casual Dining, Fine Dining)
- Appropriate address and contact information
- Industry-relevant priority levels

#### Contacts  
- Realistic job positions (Chef, General Manager, Purchasing Manager)
- Professional email formats
- Phone number formatting
- Primary contact designation

#### Interactions
- Food service communication types
- Realistic interaction subjects and descriptions
- Appropriate outcomes and follow-up actions
- Business hour scheduling

#### Opportunities
- Food service deal values and stages
- Seasonal timing considerations
- Industry-appropriate probabilities
- Contract close date patterns

### Data Consistency
- **Email Uniqueness**: Ensured across all contact generation
- **Relationship Integrity**: Proper foreign key relationships
- **Enum Compliance**: All enum fields use valid database values
- **Date Formats**: Consistent timestamp and date formatting
- **Business Logic**: Generated data follows business rules

## Testing Integration

### Factory Testing
All factories include:
- ✅ **Schema Compliance**: Match database structure exactly
- ✅ **Relationship Support**: Proper foreign key generation
- ✅ **Data Validation**: Generated data passes model validation
- ✅ **Unique Constraints**: Handle unique field requirements
- ✅ **Performance**: Efficient bulk data generation

### Test Data Sets
Factories support various testing scenarios:
- **Unit Tests**: Individual model functionality
- **Integration Tests**: Cross-model relationships
- **Performance Tests**: Large dataset generation
- **Feature Tests**: End-to-end functionality
- **Seeding**: Development database population

## Development Guidelines

### Creating New Factories
1. **Generate factory**: `php artisan make:factory ModelNameFactory`
2. **Match schema exactly**: Ensure all fields match database migration
3. **Use realistic data**: Industry-appropriate fake data
4. **Test relationships**: Verify foreign key generation works
5. **Add to documentation**: Update this README with changes

### Modifying Existing Factories
1. **Schema changes**: Update factories when migrations change
2. **Test compatibility**: Ensure existing tests still pass
3. **Document changes**: Log modifications in change log
4. **Version considerations**: Consider backward compatibility

### Factory Best Practices
- **Use appropriate Faker methods** for data types
- **Follow naming conventions** for factory files and methods
- **Include state methods** for common variations
- **Test factory output** to ensure valid data generation
- **Consider performance** for large dataset generation

## Troubleshooting

### Common Issues
1. **Schema mismatches**: Factory fields don't match migration
2. **Foreign key errors**: Relationship generation fails
3. **Unique constraint violations**: Duplicate data generation
4. **Enum value errors**: Invalid enum values used
5. **Performance issues**: Slow factory generation

### Resolution Steps
1. **Compare with migration**: Ensure field names and types match
2. **Check relationships**: Verify foreign key constraints
3. **Test factory independently**: Create single instances first
4. **Review enum definitions**: Match database enum values exactly
5. **Optimize for bulk creation**: Use efficient generation patterns

### Debugging Factory Issues
```php
// Test factory generation
$contact = Contact::factory()->make(); // Don't save to database
dd($contact->toArray()); // Inspect generated attributes

// Test with relationships
$contact = Contact::factory()->for(Organization::factory())->create();
dd($contact->organization); // Verify relationship works
```