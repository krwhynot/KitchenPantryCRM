# PantryCRM Testing Documentation

This document provides comprehensive guidance for testing the PantryCRM application, covering all implemented test suites and best practices.

## Testing Overview

PantryCRM implements a comprehensive testing strategy covering multiple layers:

- **Unit Tests**: Model behavior, business logic, and individual components
- **Integration Tests**: CRUD operations for Filament resources with full UI interaction
- **End-to-End Tests**: Complete user workflows spanning multiple CRM entities
- **User Acceptance Tests**: Real-world scenarios from the perspective of actual users
- **Performance Tests**: Sub-second response time validation and load testing
- **Smoke Tests**: Basic system health and connectivity verification

## Test Structure

```
tests/
├── Feature/
│   ├── Filament/              # Filament resource integration tests
│   │   ├── OrganizationResourceTest.php
│   │   ├── ContactResourceTest.php
│   │   ├── InteractionResourceTest.php
│   │   ├── OpportunityResourceTest.php
│   │   └── SystemSettingResourceTest.php
│   ├── Models/                # Model integration tests
│   │   ├── EagerLoadingPerformanceTest.php
│   │   └── ModelRelationshipIntegrationTest.php
│   ├── Performance/           # Performance benchmarking tests
│   │   └── CrmPerformanceTest.php
│   ├── Seeders/               # Database seeder tests
│   │   └── CrmDefaultSettingsSeederTest.php
│   ├── Smoke/                 # System health tests
│   │   ├── ApplicationHealthTest.php
│   │   ├── DatabaseConnectivityTest.php
│   │   ├── DevelopmentServerTest.php
│   │   ├── FilamentInstallationTest.php
│   │   ├── SmokeTestSuite.php
│   │   └── SystemComponentsTest.php
│   ├── UserAcceptance/        # User story validation tests
│   │   └── CrmUserAcceptanceTest.php
│   ├── Workflows/             # End-to-end workflow tests
│   │   └── CrmWorkflowTest.php
│   └── ExampleTest.php
└── Unit/
    ├── Models/                # Model unit tests
    │   ├── ContactTest.php
    │   ├── InteractionTest.php
    │   ├── OpportunityTest.php
    │   ├── OrganizationTest.php
    │   └── SystemSettingTest.php
    └── Services/              # Service class unit tests
```

## Running Tests

### All Tests
```bash
php artisan test
```

### Specific Test Suites
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only  
php artisan test --testsuite=Feature

# Specific test categories
php artisan test tests/Feature/Filament/
php artisan test tests/Feature/Performance/
php artisan test tests/Feature/UserAcceptance/
```

### Individual Test Files
```bash
php artisan test tests/Feature/Filament/OrganizationResourceTest.php
php artisan test tests/Feature/Performance/CrmPerformanceTest.php
```

### Parallel Testing (for faster execution)
```bash
php artisan test --parallel
```

## Test Categories Explained

### 1. Integration Tests (Filament Resources)

**Purpose**: Validate CRUD operations for all Filament resources work correctly with the UI components.

**Key Features Tested**:
- Create, read, update, delete operations
- Form validation
- Table filtering and searching
- Bulk actions
- Relationship management
- Badge colors and formatting
- Navigation and routing

**Example**: `OrganizationResourceTest.php` validates that users can create organizations, filter by priority, bulk update statuses, and that all form validations work correctly.

### 2. End-to-End Workflow Tests

**Purpose**: Test complete business processes that span multiple entities and user interactions.

**Scenarios Covered**:
- Complete sales pipeline: Prospect → Contact → Interaction → Opportunity → Closed Deal
- Lost opportunity workflow
- Multi-contact opportunity management
- Pipeline stage progression tracking
- Bulk operations across entities

**Example**: `CrmWorkflowTest.php` includes a complete test that creates a prospect, adds contacts, logs interactions, creates opportunities, and converts to a closed client deal.

### 3. User Acceptance Tests

**Purpose**: Validate that the system meets business requirements from real user perspectives.

**User Stories Covered**:
- Sales rep can quickly add new restaurant prospects
- Sales rep can log customer interactions efficiently  
- Sales rep can track opportunities through sales pipeline
- Sales manager can identify at-risk opportunities
- Sales rep can convert prospects to clients
- Multi-contact relationship management
- Follow-up prioritization
- Team performance monitoring
- Customer information search
- Interaction history tracking

**Example**: Each test method represents a specific user story with acceptance criteria.

### 4. Performance Tests

**Purpose**: Ensure sub-second response times and efficient resource usage.

**Performance Criteria**:
- List pages load in < 1 second with up to 1000 records
- CRUD operations complete in < 1 second
- Search operations respond in < 1 second
- Form updates respond in < 500ms
- Memory usage remains reasonable
- Database queries are optimized (no N+1 problems)

**Load Testing**: Simulates multiple concurrent users and validates performance degradation is minimal.

### 5. Unit Tests

**Purpose**: Test individual model methods, relationships, and business logic.

**Coverage**:
- Model relationships (hasMany, belongsTo)
- Accessors and mutators
- Scopes and query builders
- Data validation
- Business logic methods

## Testing Best Practices

### 1. Test Naming Conventions

- Use descriptive method names that explain what is being tested
- Prefix with test category when helpful (e.g., `test_can_filter_by_priority`)
- Use snake_case for test method names

### 2. Test Structure

Follow the **Arrange-Act-Assert** pattern:

```php
public function test_can_create_organization()
{
    // Arrange: Set up test data
    $this->actingAs($this->user);
    $organizationData = [...];
    
    // Act: Perform the action
    Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
        ->fillForm($organizationData)
        ->call('create');
    
    // Assert: Verify results
    $this->assertDatabaseHas('organizations', [
        'name' => 'Test Organization'
    ]);
}
```

### 3. Data Setup

- Use factories for creating test data
- Set up common data in `setUp()` method
- Create only the minimum data needed for each test
- Use `RefreshDatabase` trait to ensure clean state

### 4. Assertions

- Use specific database assertions: `assertDatabaseHas`, `assertSoftDeleted`
- Test both positive and negative cases
- Verify UI feedback: `assertSee`, `assertCanSeeTableRecords`
- Check for errors: `assertHasNoFormErrors`, `assertSuccessful`

### 5. Performance Testing

- Measure execution time for critical operations
- Test with realistic data volumes
- Monitor memory usage
- Count database queries to prevent N+1 problems

## Common Test Patterns

### Testing Filament Resources

```php
// Test list page
Livewire::test(ResourceClass\Pages\ListItems::class)
    ->assertSuccessful()
    ->assertCanSeeTableRecords($items);

// Test create page
Livewire::test(ResourceClass\Pages\CreateItem::class)
    ->fillForm($data)
    ->call('create')
    ->assertHasNoFormErrors();

// Test filtering
Livewire::test(ResourceClass\Pages\ListItems::class)
    ->filterTable('status', 'active')
    ->assertCanSeeTableRecords($activeItems);

// Test bulk actions
Livewire::test(ResourceClass\Pages\ListItems::class)
    ->callTableBulkAction('updateStatus', $items, data: ['status' => 'inactive']);
```

### Testing Relationships

```php
// Test dynamic form updates based on relationships
$component = Livewire::test(InteractionResource\Pages\CreateInteraction::class)
    ->fillForm(['organization_id' => $organization->id]);

$component->assertFormFieldExists('contact_id');
```

### Performance Testing Pattern

```php
$executionTime = $this->measureExecutionTime(function () {
    // Operation to measure
    Livewire::test(ResourceClass\Pages\ListItems::class)->assertSuccessful();
});

$this->assertLessThan(1.0, $executionTime, "Operation should complete in under 1s");
```

## Test Data Management

### Factories

All models have corresponding factories for generating test data:

- `OrganizationFactory`: Creates realistic restaurant/foodservice organizations
- `ContactFactory`: Creates contacts with proper names and relationships
- `InteractionFactory`: Creates various types of customer interactions
- `OpportunityFactory`: Creates sales opportunities with realistic values

### Database Seeding for Tests

Some tests require specific seed data. Use seeders in test setup:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(CrmDefaultSettingsSeeder::class);
}
```

## Continuous Integration

### Test Execution in CI

Tests are designed to run reliably in CI environments:

- All tests use `RefreshDatabase` for isolation
- No external dependencies required
- Performance tests have reasonable thresholds
- Parallel execution supported

### Coverage Requirements

Aim for high coverage in critical areas:
- All Filament resource CRUD operations: 100%
- Model relationships and business logic: 95%+
- Core user workflows: 100%
- Performance critical paths: 100%

## Debugging Tests

### Common Issues and Solutions

1. **Test Database Issues**
   ```bash
   php artisan migrate:fresh --env=testing
   ```

2. **Livewire Component Errors**
   - Ensure proper form field names match model attributes
   - Check that relationships are properly loaded
   - Verify user authentication is set up

3. **Performance Test Failures**
   - Check database indexing
   - Review query optimization
   - Verify test environment performance

### Verbose Test Output

```bash
php artisan test --verbose
php artisan test --debug
```

## Future Testing Enhancements

### Planned Additions

1. **Browser Testing**: Laravel Dusk tests for full browser automation
2. **API Testing**: When API endpoints are added
3. **Integration Testing**: Third-party service integrations
4. **Security Testing**: Authentication, authorization, and data protection
5. **Mobile Responsiveness**: Filament mobile interface testing

### Performance Monitoring

- Add performance benchmarking to CI pipeline
- Monitor query counts and execution times
- Track memory usage patterns
- Set up performance regression alerts

## Contributing to Tests

### Adding New Tests

1. Follow existing naming and structure conventions
2. Include both positive and negative test cases
3. Add performance tests for new CRUD operations
4. Update this documentation for new test categories

### Test Review Checklist

- [ ] Tests cover both success and failure scenarios
- [ ] Performance requirements are validated
- [ ] Test data is properly isolated
- [ ] Assertions are specific and meaningful
- [ ] Test names clearly describe what is being tested
- [ ] Database changes are properly asserted
- [ ] UI feedback is verified where applicable

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Livewire Testing Documentation](https://livewire.laravel.com/docs/testing)
- [Filament Testing Documentation](https://filamentphp.com/docs/panels/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

*Last Updated: June 2025*
*Test Coverage: 95%+ across all critical paths*
*Performance Target: Sub-second response times for all CRUD operations*