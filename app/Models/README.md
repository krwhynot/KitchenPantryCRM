# App Models Directory

## Directory Purpose
Contains Eloquent models for the PantryCRM application, defining data structures, relationships, and business logic for core CRM entities including organizations, contacts, interactions, opportunities, and food service principals.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | Principal.php | ADDED | Model for food service principals/suppliers with product line relationships
- 2025-06-21 | ProductLine.php | ADDED | Model for principal product lines with active status tracking
- 2025-06-21 | Organization.php | MODIFIED | Enhanced with priority labels, full address accessor, and comprehensive scopes
- 2025-06-21 | Contact.php | MODIFIED | Added full name accessor, display name, and relationship scopes
- 2025-06-21 | Opportunity.php | MODIFIED | Enhanced with stage/status labels, value formatting, and query scopes
- 2025-06-21 | Interaction.php | MODIFIED | Added type/date formatting and user relationship support
- 2025-06-21 | README.md | ADDED | Documentation and change tracking for models directory

### Change Types Legend
- **ADDED**: New model file created
- **MODIFIED**: Existing model enhanced/updated
- **DELETED**: Model file removed
- **RENAMED**: Model moved or renamed
- **BASELINE**: Initial state documentation
- **REFACTORED**: Major structural changes to model
- **FIXED**: Bug fix in model logic
- **RELATIONSHIP**: Added/modified model relationships

### File Inventory
Current model files in this directory:
- Account.php - Financial account management (pre-existing)
- Contact.php - Individual contact management ✨ ENHANCED
- Contract.php - Contract and agreement tracking (pre-existing)
- Interaction.php - Customer interaction logging ✨ ENHANCED
- Lead.php - Sales lead management (pre-existing)
- Opportunity.php - Sales opportunity tracking ✨ ENHANCED  
- Organization.php - Company/organization management ✨ ENHANCED
- Principal.php - Food service principals/suppliers 🆕 NEW
- ProductLine.php - Principal product lines 🆕 NEW
- Session.php - User session management (pre-existing)
- SystemSetting.php - Application configuration (pre-existing)
- User.php - User authentication and management (pre-existing)
- VerificationToken.php - Email verification tokens (pre-existing)

## Model Architecture Overview

### Core CRM Entities
The models represent a food service CRM with the following key relationships:

```
Organization (Companies/Restaurants)
├── hasMany → Contact (Individual contacts at organizations)
├── hasMany → Interaction (Communications and meetings)
├── hasMany → Opportunity (Sales opportunities)
└── hasMany → Contract (Signed agreements)

Principal (Food service suppliers)
├── hasMany → ProductLine (Product categories/brands)
└── hasManyThrough → Opportunity (via product lines)

User (System users)
├── hasMany → Interaction (User-generated interactions)
└── hasMany → Opportunity (Assigned opportunities)
```

### Enhanced Features (Task 23 Implementation)

#### Accessors & Mutators
- **Organization**: `priority_label`, `full_address`, `estimated_revenue_formatted`
- **Contact**: `full_name`, `display_name`
- **Opportunity**: `stage_label`, `status_label`, `value_formatted`
- **Interaction**: `type_label`, `formatted_date`
- **Principal**: `contact_display`
- **ProductLine**: `status_label`

#### Query Scopes
- **Organization**: `byPriority()`, `active()`, `bySegment()`
- **Contact**: `primary()`, `byOrganization()`, `active()`
- **Opportunity**: `byStage()`, `open()`, `won()`, `lost()`, `highValue()`
- **Interaction**: `recent()`, `byType()`, `byOutcome()`
- **Principal**: `active()`, `withActiveProductLines()`
- **ProductLine**: `active()`, `byPrincipal()`

### Data Integrity Features
- **UUID Primary Keys**: All core entities use UUID for better scalability
- **Foreign Key Constraints**: Proper cascade deletes and null handling
- **Timestamps**: Automatic created_at and updated_at tracking
- **Soft Deletes**: Available on key models (where implemented)
- **Validation**: Model-level validation rules (where implemented)

### Testing Coverage
All enhanced models include:
- ✅ **Unit Tests**: Individual model functionality testing
- ✅ **Factory Support**: Comprehensive model factories for testing
- ✅ **Relationship Tests**: Validation of model associations
- ✅ **Integration Tests**: Cross-model functionality testing
- ✅ **Performance Tests**: N+1 query prevention and eager loading

## Development Guidelines

### Adding New Models
1. Create model class with appropriate relationships
2. Define factory for testing data generation
3. Add unit tests for model functionality
4. Document relationships in this README
5. Update change log with model addition

### Modifying Existing Models
1. Add appropriate accessors/mutators for data formatting
2. Implement useful query scopes for common operations
3. Update or add tests for new functionality
4. Document changes in this README
5. Consider backward compatibility

### Relationship Best Practices
- Use typed relationships (belongsTo, hasMany, etc.)
- Implement cascade deletes appropriately
- Add foreign key constraints in migrations
- Use eager loading to prevent N+1 queries
- Document complex relationships clearly

### Performance Considerations
- Use query scopes for efficient database queries
- Implement proper indexing in migrations
- Consider caching for frequently accessed data
- Use chunking for large dataset operations
- Monitor query performance in development