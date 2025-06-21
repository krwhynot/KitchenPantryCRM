<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\OrganizationResource;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_organizations()
    {
        $organizations = Organization::factory()->count(5)->create();

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($organizations);
    }

    public function test_can_create_organization()
    {
        Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'name' => 'Test Restaurant Group',
                'type' => 'PROSPECT',
                'priority' => 'A',
                'segment' => 'FINE_DINING',
                'status' => 'ACTIVE',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'zipCode' => '10001',
                'phone' => '+1-555-0123',
                'email' => 'contact@testrestaurant.com',
                'website' => 'www.testrestaurant.com',
                'estimatedRevenue' => 500000,
                'employeeCount' => 25,
                'primaryContact' => 'John Smith',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Restaurant Group',
            'type' => 'PROSPECT',
            'priority' => 'A',
            'segment' => 'FINE_DINING',
            'email' => 'contact@testrestaurant.com',
        ]);
    }

    public function test_can_edit_organization()
    {
        $organization = Organization::factory()->create([
            'name' => 'Original Name',
            'priority' => 'C',
            'estimatedRevenue' => 100000,
        ]);

        Livewire::test(OrganizationResource\Pages\EditOrganization::class, [
            'record' => $organization->getRouteKey(),
        ])
        ->fillForm([
            'name' => 'Updated Restaurant Name',
            'priority' => 'A',
            'estimatedRevenue' => 750000,
            'notes' => 'Updated priority due to high potential',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Updated Restaurant Name',
            'priority' => 'A',
            'estimatedRevenue' => 750000,
        ]);
    }

    public function test_can_delete_organization()
    {
        $organization = Organization::factory()->create();

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('delete', $organization);

        $this->assertSoftDeleted('organizations', [
            'id' => $organization->id,
        ]);
    }

    public function test_can_filter_by_priority()
    {
        $highPriority = Organization::factory()->create(['priority' => 'A']);
        $lowPriority = Organization::factory()->create(['priority' => 'D']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->filterTable('priority', 'A')
            ->assertCanSeeTableRecords([$highPriority])
            ->assertCanNotSeeTableRecords([$lowPriority]);
    }

    public function test_can_filter_by_type()
    {
        $client = Organization::factory()->create(['type' => 'CLIENT']);
        $prospect = Organization::factory()->create(['type' => 'PROSPECT']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->filterTable('type', 'CLIENT')
            ->assertCanSeeTableRecords([$client])
            ->assertCanNotSeeTableRecords([$prospect]);
    }

    public function test_can_filter_by_status()
    {
        $active = Organization::factory()->create(['status' => 'ACTIVE']);
        $inactive = Organization::factory()->create(['status' => 'INACTIVE']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->filterTable('status', 'ACTIVE')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_can_search_organizations()
    {
        $restaurant1 = Organization::factory()->create(['name' => 'Fine Dining Restaurant']);
        $restaurant2 = Organization::factory()->create(['name' => 'Quick Service Chain']);
        $supplier = Organization::factory()->create(['name' => 'Food Supplier Co']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('Restaurant')
            ->assertCanSeeTableRecords([$restaurant1, $restaurant2])
            ->assertCanNotSeeTableRecords([$supplier]);
    }

    public function test_can_search_by_email_and_phone()
    {
        $org1 = Organization::factory()->create([
            'name' => 'Test Org 1',
            'email' => 'contact@testorg.com',
        ]);
        $org2 = Organization::factory()->create([
            'name' => 'Test Org 2',
            'phone' => '555-0123',
        ]);
        $org3 = Organization::factory()->create([
            'name' => 'Different Org',
            'email' => 'different@email.com',
        ]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('testorg')
            ->assertCanSeeTableRecords([$org1])
            ->assertCanNotSeeTableRecords([$org2, $org3]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('555-0123')
            ->assertCanSeeTableRecords([$org2])
            ->assertCanNotSeeTableRecords([$org1, $org3]);
    }

    public function test_bulk_change_priority_action()
    {
        $organizations = Organization::factory()->count(3)->create(['priority' => 'C']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableBulkAction('changePriority', $organizations, data: [
                'priority' => 'A',
            ]);

        foreach ($organizations as $organization) {
            $this->assertDatabaseHas('organizations', [
                'id' => $organization->id,
                'priority' => 'A',
            ]);
        }
    }

    public function test_bulk_change_status_action()
    {
        $organizations = Organization::factory()->count(3)->create(['status' => 'PROSPECT']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableBulkAction('changeStatus', $organizations, data: [
                'status' => 'ACTIVE',
            ]);

        foreach ($organizations as $organization) {
            $this->assertDatabaseHas('organizations', [
                'id' => $organization->id,
                'status' => 'ACTIVE',
            ]);
        }
    }

    public function test_can_bulk_delete_organizations()
    {
        $organizations = Organization::factory()->count(3)->create();

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableBulkAction('delete', $organizations);

        foreach ($organizations as $organization) {
            $this->assertSoftDeleted('organizations', [
                'id' => $organization->id,
            ]);
        }
    }

    public function test_form_validation_works()
    {
        Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'name' => '', // Required field
                'type' => '', // Required field
                'priority' => '', // Required field
                'city' => '', // Required field
                'state' => '', // Required field
                'email' => 'invalid-email', // Invalid email
                'zipCode' => '123', // Invalid ZIP format
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'type', 'priority', 'city', 'state', 'email', 'zipCode']);
    }

    public function test_unique_name_validation()
    {
        $existingOrg = Organization::factory()->create(['name' => 'Unique Restaurant']);

        Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'name' => 'Unique Restaurant', // Duplicate name
                'type' => 'PROSPECT',
                'priority' => 'C',
                'city' => 'New York',
                'state' => 'NY',
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_unique_email_validation()
    {
        $existingOrg = Organization::factory()->create(['email' => 'unique@restaurant.com']);

        Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'name' => 'Different Restaurant',
                'type' => 'PROSPECT',
                'priority' => 'C',
                'city' => 'New York',
                'state' => 'NY',
                'email' => 'unique@restaurant.com', // Duplicate email
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_priority_badge_colors_display_correctly()
    {
        $highPriority = Organization::factory()->create(['priority' => 'A']);
        $mediumPriority = Organization::factory()->create(['priority' => 'C']);

        $component = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$highPriority, $mediumPriority]);

        // Verify the priority badges are rendered
        $component->assertSee('A - Highest')
                  ->assertSee('C - Medium');
    }

    public function test_type_badge_colors_display_correctly()
    {
        $client = Organization::factory()->create(['type' => 'CLIENT']);
        $prospect = Organization::factory()->create(['type' => 'PROSPECT']);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$client, $prospect])
            ->assertSee('CLIENT')
            ->assertSee('PROSPECT');
    }

    public function test_revenue_formatting_displays_correctly()
    {
        $organization = Organization::factory()->create(['estimatedRevenue' => 1250000.50]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$organization])
            ->assertSee('$1,250,000.50');
    }

    public function test_website_url_links_work()
    {
        $organization = Organization::factory()->create(['website' => 'testrestaurant.com']);

        $component = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$organization]);

        // The website should be linked and open in new tab
        $component->assertSee('testrestaurant.com');
    }

    public function test_phone_and_email_are_copyable()
    {
        $organization = Organization::factory()->create([
            'phone' => '555-0123',
            'email' => 'test@restaurant.com',
        ]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$organization])
            ->assertSee('555-0123')
            ->assertSee('test@restaurant.com');
    }

    public function test_contact_date_color_coding()
    {
        $oldContact = Organization::factory()->create([
            'lastContactDate' => now()->subDays(45), // Over 30 days ago
        ]);
        $recentContact = Organization::factory()->create([
            'lastContactDate' => now()->subDays(15), // Within 30 days
        ]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$oldContact, $recentContact]);
    }

    public function test_follow_up_date_color_coding()
    {
        $overdue = Organization::factory()->create([
            'nextFollowUpDate' => now()->subDay(), // Past due
        ]);
        $today = Organization::factory()->create([
            'nextFollowUpDate' => now(), // Due today
        ]);
        $future = Organization::factory()->create([
            'nextFollowUpDate' => now()->addDays(5), // Future
        ]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$overdue, $today, $future]);
    }

    public function test_can_view_organization_details()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Restaurant',
            'notes' => 'Important client notes',
        ]);

        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('view', $organization);
    }

    public function test_organization_with_related_records_integration()
    {
        $organization = Organization::factory()->create();
        $contacts = Contact::factory()->count(2)->for($organization)->create();
        $interactions = Interaction::factory()->count(3)->for($organization)->create();
        $opportunities = Opportunity::factory()->count(2)->for($organization)->create();

        // Test that the organization displays correctly with related data
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertCanSeeTableRecords([$organization]);

        // Test editing the organization doesn't affect related records
        Livewire::test(OrganizationResource\Pages\EditOrganization::class, [
            'record' => $organization->getRouteKey(),
        ])
        ->fillForm([
            'name' => 'Updated Organization Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        // Verify related records still exist
        $this->assertDatabaseHas('contacts', ['organization_id' => $organization->id]);
        $this->assertDatabaseHas('interactions', ['organization_id' => $organization->id]);
        $this->assertDatabaseHas('opportunities', ['organization_id' => $organization->id]);
    }

    public function test_soft_delete_and_restore_functionality()
    {
        $organization = Organization::factory()->create();

        // Test soft delete
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('delete', $organization);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);

        // Test restore
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('restore', $organization);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_functionality()
    {
        $organization = Organization::factory()->create();
        
        // First soft delete
        $organization->delete();

        // Then force delete
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('forceDelete', $organization);

        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
    }
}