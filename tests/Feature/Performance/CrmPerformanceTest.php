<?php

namespace Tests\Feature\Performance;

use App\Filament\Resources\ContactResource;
use App\Filament\Resources\InteractionResource;
use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\OrganizationResource;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Performance Benchmarking Tests for PantryCRM
 * 
 * These tests ensure that core CRUD operations meet sub-second response time requirements
 * and that the system can handle expected data volumes efficiently.
 */
class CrmPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected float $maxResponseTime = 1.0; // Sub-second requirement (1000ms)

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Helper method to measure execution time
     */
    protected function measureExecutionTime(callable $callback): float
    {
        $startTime = microtime(true);
        $callback();
        $endTime = microtime(true);
        
        return $endTime - $startTime;
    }

    /**
     * PERF-001: Organization list page should load within sub-second response time
     * with up to 1000 records
     */
    public function test_organization_list_performance_with_large_dataset()
    {
        // Create large dataset - 1000 organizations
        Organization::factory()->count(1000)->create();

        $executionTime = $this->measureExecutionTime(function () {
            $component = Livewire::test(OrganizationResource\Pages\ListOrganizations::class);
            $component->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Organization list should load in under {$this->maxResponseTime}s, took {$executionTime}s"
        );

        // Test with pagination
        $paginationTime = $this->measureExecutionTime(function () {
            Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
                ->set('tableRecordsPerPage', 25) // Paginate to 25 per page
                ->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $paginationTime,
            "Paginated organization list should load in under {$this->maxResponseTime}s, took {$paginationTime}s"
        );
    }

    /**
     * PERF-002: Organization creation should complete within sub-second response time
     */
    public function test_organization_creation_performance()
    {
        $organizationData = [
            'name' => 'Performance Test Restaurant',
            'type' => 'PROSPECT',
            'priority' => 'A',
            'segment' => 'FINE_DINING',
            'status' => 'ACTIVE',
            'address' => '123 Performance St',
            'city' => 'Speed City',
            'state' => 'TX',
            'zipCode' => '12345',
            'phone' => '555-PERF',
            'email' => 'perf@test.com',
            'website' => 'www.perftest.com',
            'estimatedRevenue' => 500000,
            'employeeCount' => 25,
            'primaryContact' => 'Speed Tester',
        ];

        $executionTime = $this->measureExecutionTime(function () use ($organizationData) {
            Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
                ->fillForm($organizationData)
                ->call('create')
                ->assertHasNoFormErrors();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Organization creation should complete in under {$this->maxResponseTime}s, took {$executionTime}s"
        );

        // Verify the record was created
        $this->assertDatabaseHas('organizations', [
            'name' => 'Performance Test Restaurant',
            'email' => 'perf@test.com',
        ]);
    }

    /**
     * PERF-003: Organization search should return results within sub-second response time
     * even with large datasets
     */
    public function test_organization_search_performance()
    {
        // Create large dataset with some specific search targets
        Organization::factory()->count(950)->create();
        Organization::factory()->count(50)->create(['name' => 'Pizza Palace ' . rand(1, 50)]);

        $searchTime = $this->measureExecutionTime(function () {
            Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
                ->searchTable('Pizza')
                ->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $searchTime,
            "Organization search should complete in under {$this->maxResponseTime}s, took {$searchTime}s"
        );
    }

    /**
     * PERF-004: Contact list with related organization data should load efficiently
     */
    public function test_contact_list_performance_with_relationships()
    {
        // Create organizations and contacts with relationships
        $organizations = Organization::factory()->count(100)->create();
        
        foreach ($organizations as $organization) {
            Contact::factory()->count(rand(1, 5))->for($organization)->create();
        }

        $executionTime = $this->measureExecutionTime(function () {
            Livewire::test(ContactResource\Pages\ListContacts::class)
                ->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Contact list with relationships should load in under {$this->maxResponseTime}s, took {$executionTime}s"
        );
    }

    /**
     * PERF-005: Contact creation with organization lookup should be fast
     */
    public function test_contact_creation_performance()
    {
        $organization = Organization::factory()->create();

        $contactData = [
            'firstName' => 'Speed',
            'lastName' => 'Test',
            'email' => 'speed@test.com',
            'phone' => '555-SPEED',
            'organization_id' => $organization->id,
            'position' => 'Performance Tester',
            'isPrimary' => true,
        ];

        $executionTime = $this->measureExecutionTime(function () use ($contactData) {
            Livewire::test(ContactResource\Pages\CreateContact::class)
                ->fillForm($contactData)
                ->call('create')
                ->assertHasNoFormErrors();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Contact creation should complete in under {$this->maxResponseTime}s, took {$executionTime}s"
        );
    }

    /**
     * PERF-006: Interaction logging should be very fast for frequent use
     */
    public function test_interaction_logging_performance()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        $interactionData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'CALL',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 15,
            'subject' => 'Performance test interaction',
            'notes' => 'Quick performance test',
            'outcome' => 'POSITIVE',
            'priority' => 'medium',
        ];

        $executionTime = $this->measureExecutionTime(function () use ($interactionData) {
            Livewire::test(InteractionResource\Pages\CreateInteraction::class)
                ->fillForm($interactionData)
                ->call('create')
                ->assertHasNoFormErrors();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Interaction logging should complete in under {$this->maxResponseTime}s, took {$executionTime}s"
        );
    }

    /**
     * PERF-007: Interaction list with multiple filters should perform well
     */
    public function test_interaction_list_filtering_performance()
    {
        // Create test data
        $organizations = Organization::factory()->count(50)->create();
        $contacts = [];
        
        foreach ($organizations as $organization) {
            $contacts[] = Contact::factory()->for($organization)->create();
        }

        // Create 500 interactions
        foreach (range(1, 500) as $i) {
            $randomOrg = $organizations->random();
            $randomContact = $contacts[array_rand($contacts)];
            
            Interaction::factory()
                ->for($randomOrg)
                ->for($randomContact)
                ->for($this->user)
                ->create([
                    'type' => ['CALL', 'EMAIL', 'MEETING', 'VISIT'][array_rand(['CALL', 'EMAIL', 'MEETING', 'VISIT'])],
                    'outcome' => ['POSITIVE', 'NEGATIVE', 'NEUTRAL'][array_rand(['POSITIVE', 'NEGATIVE', 'NEUTRAL'])],
                ]);
        }

        // Test filtering performance
        $filterTime = $this->measureExecutionTime(function () {
            Livewire::test(InteractionResource\Pages\ListInteractions::class)
                ->filterTable('type', ['CALL'])
                ->filterTable('outcome', ['POSITIVE'])
                ->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $filterTime,
            "Interaction filtering should complete in under {$this->maxResponseTime}s, took {$filterTime}s"
        );
    }

    /**
     * PERF-008: Opportunity creation with dynamic form updates should be responsive
     */
    public function test_opportunity_creation_performance()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        $opportunityData = [
            'title' => 'Performance Test Opportunity',
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'user_id' => $this->user->id,
            'description' => 'Testing opportunity creation performance',
            'stage' => 'lead',
            'probability' => 25,
            'status' => 'open',
            'value' => 50000.00,
            'expectedCloseDate' => now()->addDays(30)->format('Y-m-d'),
            'priority' => 'high',
        ];

        $executionTime = $this->measureExecutionTime(function () use ($opportunityData) {
            Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
                ->fillForm($opportunityData)
                ->call('create')
                ->assertHasNoFormErrors();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $executionTime,
            "Opportunity creation should complete in under {$this->maxResponseTime}s, took {$executionTime}s"
        );
    }

    /**
     * PERF-009: Opportunity pipeline filters should perform well with large dataset
     */
    public function test_opportunity_pipeline_filtering_performance()
    {
        // Create comprehensive test dataset
        $organizations = Organization::factory()->count(20)->create();
        $contacts = [];
        
        foreach ($organizations as $organization) {
            $contacts[] = Contact::factory()->for($organization)->create();
        }

        // Create 300 opportunities across different stages
        $stages = ['lead', 'prospect', 'proposal', 'negotiation', 'closed'];
        $statuses = ['open', 'won', 'lost'];
        $priorities = ['low', 'medium', 'high'];

        foreach (range(1, 300) as $i) {
            $randomOrg = $organizations->random();
            $randomContact = $contacts[array_rand($contacts)];
            
            Opportunity::factory()
                ->for($randomOrg)
                ->for($randomContact)
                ->for($this->user)
                ->create([
                    'stage' => $stages[array_rand($stages)],
                    'status' => $statuses[array_rand($statuses)],
                    'priority' => $priorities[array_rand($priorities)],
                    'value' => rand(5000, 100000),
                ]);
        }

        // Test complex filtering performance
        $complexFilterTime = $this->measureExecutionTime(function () {
            Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
                ->filterTable('stage', 'proposal')
                ->filterTable('status', 'open')
                ->filterTable('priority', 'high')
                ->filterTable('high_value')
                ->assertSuccessful();
        });

        $this->assertLessThan(
            $this->maxResponseTime,
            $complexFilterTime,
            "Complex opportunity filtering should complete in under {$this->maxResponseTime}s, took {$complexFilterTime}s"
        );
    }

    /**
     * PERF-010: Bulk operations should complete efficiently
     */
    public function test_bulk_operations_performance()
    {
        // Create 100 organizations for bulk operations
        $organizations = Organization::factory()->count(100)->create(['priority' => 'C']);

        // Test bulk priority update
        $bulkUpdateTime = $this->measureExecutionTime(function () use ($organizations) {
            Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
                ->callTableBulkAction('changePriority', $organizations->take(50), data: [
                    'priority' => 'A',
                ]);
        });

        $this->assertLessThan(
            $this->maxResponseTime * 2, // Allow 2 seconds for bulk operations
            $bulkUpdateTime,
            "Bulk priority update should complete in under 2s, took {$bulkUpdateTime}s"
        );

        // Verify updates were applied
        $this->assertEquals(50, Organization::where('priority', 'A')->count());
    }

    /**
     * PERF-011: Database queries should be optimized (N+1 query prevention)
     */
    public function test_database_query_optimization()
    {
        // Create test data with relationships
        $organizations = Organization::factory()->count(50)->create();
        
        foreach ($organizations as $organization) {
            $contact = Contact::factory()->for($organization)->create();
            Interaction::factory()->count(2)->for($organization)->for($contact)->for($this->user)->create();
            Opportunity::factory()->count(1)->for($organization)->for($contact)->for($this->user)->create();
        }

        // Count queries during interaction list load (should use eager loading)
        \DB::enableQueryLog();
        
        $component = Livewire::test(InteractionResource\Pages\ListInteractions::class);
        $component->assertSuccessful();
        
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Should not have excessive queries (indicative of N+1 problems)
        $this->assertLessThan(
            20, // Reasonable limit for query count
            $queryCount,
            "Interaction list should not generate excessive queries. Found {$queryCount} queries."
        );
    }

    /**
     * PERF-012: Form field updates should be responsive
     */
    public function test_dynamic_form_responsiveness()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        // Test organization selection triggering contact dropdown update
        $formUpdateTime = $this->measureExecutionTime(function () use ($organization) {
            $component = Livewire::test(InteractionResource\Pages\CreateInteraction::class)
                ->fillForm(['organization_id' => $organization->id]);
                
            $component->assertFormFieldExists('contact_id');
        });

        $this->assertLessThan(
            0.5, // Form updates should be very fast (500ms)
            $formUpdateTime,
            "Dynamic form updates should complete in under 0.5s, took {$formUpdateTime}s"
        );
    }

    /**
     * PERF-013: Memory usage should remain reasonable with large datasets
     */
    public function test_memory_usage_with_large_dataset()
    {
        $initialMemory = memory_get_usage(true);

        // Create substantial dataset
        Organization::factory()->count(500)->create();
        
        // Load the list page
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertSuccessful();
            
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        $memoryIncreaseInMB = $memoryIncrease / 1024 / 1024;

        // Memory increase should be reasonable (less than 50MB for this test)
        $this->assertLessThan(
            50,
            $memoryIncreaseInMB,
            "Memory usage should remain reasonable. Increased by {$memoryIncreaseInMB}MB"
        );
    }

    /**
     * PERF-014: Concurrent user simulation
     */
    public function test_concurrent_access_simulation()
    {
        // Create test data
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        // Simulate multiple users accessing the same resources
        $users = User::factory()->count(5)->create();
        $executionTimes = [];

        foreach ($users as $testUser) {
            $this->actingAs($testUser);
            
            $time = $this->measureExecutionTime(function () {
                Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
                    ->assertSuccessful();
            });
            
            $executionTimes[] = $time;
        }

        $averageTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);

        $this->assertLessThan(
            $this->maxResponseTime,
            $averageTime,
            "Average response time under concurrent access should be under {$this->maxResponseTime}s, was {$averageTime}s"
        );

        $this->assertLessThan(
            $this->maxResponseTime * 1.5, // Allow 50% degradation under load
            $maxTime,
            "Max response time under concurrent access should be reasonable, was {$maxTime}s"
        );
    }

    /**
     * Performance summary test - provides overall system performance overview
     */
    public function test_performance_summary()
    {
        // Create realistic dataset
        $organizations = Organization::factory()->count(100)->create();
        $contacts = [];
        $interactions = [];
        $opportunities = [];

        foreach ($organizations as $organization) {
            $orgContacts = Contact::factory()->count(rand(1, 3))->for($organization)->create();
            $contacts = array_merge($contacts, $orgContacts->toArray());
            
            foreach ($orgContacts as $contact) {
                $contactInteractions = Interaction::factory()->count(rand(1, 5))
                    ->for($organization)
                    ->for($contact)
                    ->for($this->user)
                    ->create();
                $interactions = array_merge($interactions, $contactInteractions->toArray());
                
                if (rand(1, 3) === 1) { // 1/3 chance of opportunity
                    $opportunity = Opportunity::factory()
                        ->for($organization)
                        ->for($contact)
                        ->for($this->user)
                        ->create();
                    $opportunities[] = $opportunity;
                }
            }
        }

        $performanceResults = [];

        // Test each major operation
        $operations = [
            'Organization List' => fn() => Livewire::test(OrganizationResource\Pages\ListOrganizations::class)->assertSuccessful(),
            'Contact List' => fn() => Livewire::test(ContactResource\Pages\ListContacts::class)->assertSuccessful(),
            'Interaction List' => fn() => Livewire::test(InteractionResource\Pages\ListInteractions::class)->assertSuccessful(),
            'Opportunity List' => fn() => Livewire::test(OpportunityResource\Pages\ListOpportunities::class)->assertSuccessful(),
        ];

        foreach ($operations as $operationName => $operation) {
            $time = $this->measureExecutionTime($operation);
            $performanceResults[$operationName] = $time;
            
            $this->assertLessThan(
                $this->maxResponseTime,
                $time,
                "{$operationName} should complete in under {$this->maxResponseTime}s, took {$time}s"
            );
        }

        // Output performance summary (visible in test output)
        $this->addToAssertionCount(1); // Ensure test is counted
        
        echo "\n\n=== CRM Performance Summary ===\n";
        echo "Dataset: " . count($organizations) . " orgs, " . count($contacts) . " contacts, " . count($interactions) . " interactions, " . count($opportunities) . " opportunities\n";
        foreach ($performanceResults as $operation => $time) {
            echo sprintf("%-20s: %.3fs\n", $operation, $time);
        }
        echo "Target: < {$this->maxResponseTime}s per operation\n";
        echo "================================\n\n";
    }
}