# Feature Tests for PantryCRM

This directory contains integration and feature tests that validate the system's functionality from a user perspective.

## Directory Structure

### Filament/ - Resource Integration Tests
Tests for all Filament admin panel resources, ensuring CRUD operations work correctly with the UI.

- **OrganizationResourceTest.php**: Tests organization management including creation, editing, filtering, bulk operations, and relationship handling
- **ContactResourceTest.php**: Tests contact management with organization relationships and dynamic form updates
- **InteractionResourceTest.php**: Tests interaction logging with multi-entity relationships and filtering
- **OpportunityResourceTest.php**: Tests sales pipeline management including kanban functionality and stage transitions
- **SystemSettingResourceTest.php**: Tests system configuration management

### Performance/ - Performance Benchmarking
Tests that ensure the system meets sub-second response time requirements.

- **CrmPerformanceTest.php**: Comprehensive performance testing for all CRUD operations, search functionality, and bulk operations

### UserAcceptance/ - User Story Validation
Tests that validate the system meets business requirements from actual user perspectives.

- **CrmUserAcceptanceTest.php**: 10 key user acceptance tests covering sales rep and manager workflows

### Workflows/ - End-to-End Process Tests
Tests that validate complete business processes spanning multiple entities.

- **CrmWorkflowTest.php**: Complete CRM workflows from prospect to closed deal, including lost opportunities and multi-contact scenarios

### Models/ - Model Integration Tests
Tests for model relationships and data integrity at the integration level.

- **EagerLoadingPerformanceTest.php**: Tests to prevent N+1 query problems
- **ModelRelationshipIntegrationTest.php**: Tests complex model relationships

### Seeders/ - Database Seeder Tests
Tests that validate database seeders work correctly.

- **CrmDefaultSettingsSeederTest.php**: Tests CRM system configuration seeding

### Smoke/ - System Health Tests
Basic tests that verify the system is functional and properly configured.

- **ApplicationHealthTest.php**: Tests basic application functionality
- **DatabaseConnectivityTest.php**: Tests database connection and basic queries
- **DevelopmentServerTest.php**: Tests development server functionality
- **FilamentInstallationTest.php**: Tests Filament admin panel installation
- **SmokeTestSuite.php**: Coordinated smoke test execution
- **SystemComponentsTest.php**: Tests system component integration

## Key Testing Patterns

### Filament Resource Testing
```php
// Test CRUD operations
Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
    ->fillForm($data)
    ->call('create')
    ->assertHasNoFormErrors();

// Test filtering and search
Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
    ->filterTable('priority', 'A')
    ->searchTable('Restaurant')
    ->assertCanSeeTableRecords($expectedRecords);

// Test bulk actions
Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
    ->callTableBulkAction('changePriority', $records, data: ['priority' => 'A']);
```

### Workflow Testing
```php
// Test complete business processes
public function test_complete_crm_workflow_from_prospect_to_closed_deal()
{
    // 1. Create organization
    // 2. Add contacts
    // 3. Log interactions
    // 4. Create opportunity
    // 5. Move through pipeline stages
    // 6. Convert to client
    // 7. Close deal
    // Verify data integrity throughout
}
```

### Performance Testing
```php
$executionTime = $this->measureExecutionTime(function () {
    Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
        ->assertSuccessful();
});

$this->assertLessThan(1.0, $executionTime, "Should load in under 1 second");
```

## Running Feature Tests

### All Feature Tests
```bash
php artisan test --testsuite=Feature
```

### Specific Categories
```bash
# Filament resource tests
php artisan test tests/Feature/Filament/

# Performance tests
php artisan test tests/Feature/Performance/

# User acceptance tests
php artisan test tests/Feature/UserAcceptance/

# Workflow tests
php artisan test tests/Feature/Workflows/
```

### Individual Test Files
```bash
php artisan test tests/Feature/Filament/OrganizationResourceTest.php
```

## Test Coverage Goals

- **CRUD Operations**: 100% coverage for all Filament resources
- **User Workflows**: 100% coverage for critical business processes
- **Performance**: Sub-second response times for all operations
- **Data Integrity**: All relationships and constraints validated
- **UI Functionality**: All filters, searches, and bulk actions tested

## Performance Benchmarks

All feature tests should meet these performance criteria:
- List pages: < 1 second with up to 1000 records
- Create operations: < 1 second
- Search operations: < 1 second
- Form updates: < 500ms
- Bulk operations: < 2 seconds

## Common Test Scenarios

### Organization Management
- Create prospects and clients
- Update priority and status
- Filter by various criteria
- Bulk operations on multiple records
- Relationship management with contacts

### Contact Management
- Create contacts linked to organizations
- Manage primary contact status
- Filter by organization and status
- Dynamic form updates based on organization selection

### Interaction Logging
- Quick interaction entry (30-second target)
- Multi-entity relationship handling
- Follow-up tracking and prioritization
- Search and filter by multiple criteria

### Opportunity Pipeline
- Create opportunities with stage progression
- Move through sales pipeline stages
- Track probability and value changes
- Filter by various business criteria
- Kanban board functionality

### End-to-End Workflows
- Complete sales cycle from prospect to client
- Lost opportunity handling
- Multi-contact opportunity management
- Search and discovery workflows

## Data Setup

All feature tests use Laravel factories and the `RefreshDatabase` trait to ensure:
- Clean test environment for each test
- Realistic test data that matches production patterns
- Proper relationship setup between entities
- Performance testing with appropriate data volumes

## Integration Points

Feature tests validate integration between:
- Filament UI components and Laravel models
- Form validation and database constraints
- Search and filtering with database queries
- Relationship management across entities
- User authentication and authorization
- Performance optimization and query efficiency

---

For detailed information about specific test files and methods, see the individual test files and the main testing documentation in `/tests/README.md`.