# App Models Directory

## Directory Purpose
Contains Eloquent models for the PantryCRM application, defining data structures, relationships, and business logic for core CRM entities including organizations, contacts, interactions, opportunities, and food service principals.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | OpportunityStageHistory.php | ADDED | Model for tracking opportunity stage transitions with user attribution and notes
- 2025-06-21 | Opportunity.php | ENHANCED | Complete pipeline enhancement with stage tracking, probability calculations, and advanced scopes
- 2025-06-21 | Interaction.php | ENHANCED | Complete optimization for 30-second entry with enhanced fields, scopes, and accessors
- 2025-06-21 | InteractionResource.php | ENHANCED | Lightning-fast form design with smart defaults, context-aware pre-filling, and keyboard optimization
- 2025-06-21 | InteractionExportAction.php | ADDED | Advanced export action with date ranges, filtering, and comprehensive metadata
- 2025-06-21 | InteractionImportAction.php | ADDED | Smart import action supporting JSON/CSV with conflict resolution and data validation
- 2025-06-21 | QuickInteractionModal.php | ADDED | Lightning-fast modal component for 15-20 second interaction entry with keyboard shortcuts
- 2025-06-21 | QuickInteractionWidget.php | ADDED | Dashboard widget for immediate interaction logging with performance tracking
- 2025-06-21 | Contact.php | ENHANCED | Added SoftDeletes trait for comprehensive contact delete management
- 2025-06-21 | ContactResource.php | ENHANCED | Complete overhaul with 6-section organized forms, advanced filters, bulk actions, and soft deletes
- 2025-06-21 | ContactExportAction.php | ADDED | Custom export action for contacts with JSON/CSV formats and comprehensive data
- 2025-06-21 | ContactImportAction.php | ADDED | Advanced import action supporting JSON/CSV with conflict resolution and validation
- 2025-06-21 | InteractionsRelationManager.php | ENHANCED | Enhanced relation manager with detailed forms, filters, and smart columns
- 2025-06-21 | OpportunitiesRelationManager.php | ENHANCED | Enhanced relation manager for contact opportunities with advanced features
- 2025-06-21 | Organization.php | ENHANCED | Added SoftDeletes trait for comprehensive delete management
- 2025-06-21 | OrganizationResource.php | ENHANCED | Complete overhaul with advanced forms, filters, bulk actions, and soft deletes
- 2025-06-21 | OrganizationExportAction.php | ADDED | Custom export action for organizations with comprehensive data
- 2025-06-21 | OrganizationImportAction.php | ADDED | Advanced import action supporting JSON/CSV with conflict resolution
- 2025-06-21 | SystemSetting.php | REFACTORED | Major enhancement with caching, type validation, and performance optimization
- 2025-06-21 | SettingsExportAction.php | ADDED | Custom Filament action for JSON settings export with versioning
- 2025-06-21 | SettingsImportAction.php | ADDED | Custom Filament action for settings import with conflict resolution
- 2025-06-21 | CrmInteractionTypesSeeder.php | ENHANCED | Added environment-specific validation and comprehensive demo data
- 2025-06-21 | CrmMarketSegmentsSeeder.php | ENHANCED | Added food service market segments with business intelligence
- 2025-06-21 | CrmDistributorOptionsSeeder.php | ENHANCED | Added supply chain distributor options with operational details
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