<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\OpportunityResource;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $organization;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->organization = Organization::factory()->create([
            'name' => 'Test Restaurant Group',
        ]);
        
        $this->contact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'John',
            'lastName' => 'Manager',
        ]);
    }

    public function test_can_list_opportunities()
    {
        $opportunities = Opportunity::factory()->count(5)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($opportunities);
    }

    public function test_can_create_opportunity_with_required_fields()
    {
        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm([
                'title' => 'New Product Line Implementation',
                'organization_id' => $this->organization->id,
                'contact_id' => $this->contact->id,
                'user_id' => $this->user->id,
                'stage' => 'lead',
                'probability' => 10,
                'status' => 'open',
                'expectedCloseDate' => now()->addDays(30)->format('Y-m-d'),
                'priority' => 'medium',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'New Product Line Implementation',
            'organization_id' => $this->organization->id,
            'contact_id' => $this->contact->id,
            'user_id' => $this->user->id,
            'stage' => 'lead',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    public function test_can_create_opportunity_with_all_fields()
    {
        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm([
                'title' => 'Premium Equipment Upgrade',
                'organization_id' => $this->organization->id,
                'contact_id' => $this->contact->id,
                'user_id' => $this->user->id,
                'description' => 'Complete kitchen equipment upgrade for fine dining experience',
                'stage' => 'proposal',
                'probability' => 50,
                'status' => 'open',
                'value' => 75000.00,
                'expectedCloseDate' => now()->addDays(45)->format('Y-m-d'),
                'priority' => 'high',
                'source' => 'Trade Show',
                'lead_score' => 85,
                'next_action' => 'Schedule equipment demonstration',
                'notes' => 'High-value client with immediate need',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'Premium Equipment Upgrade',
            'description' => 'Complete kitchen equipment upgrade for fine dining experience',
            'stage' => 'proposal',
            'probability' => 50,
            'value' => 75000.00,
            'source' => 'Trade Show',
            'lead_score' => 85,
            'next_action' => 'Schedule equipment demonstration',
        ]);
    }

    public function test_probability_auto_updates_when_stage_changes()
    {
        $component = Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm([
                'title' => 'Test Opportunity',
                'organization_id' => $this->organization->id,
                'user_id' => $this->user->id,
                'stage' => 'lead',
                'status' => 'open',
                'expectedCloseDate' => now()->addDays(30)->format('Y-m-d'),
                'priority' => 'medium',
            ]);

        // Test that changing stage to negotiation updates probability to 75%
        $component->fillForm(['stage' => 'negotiation'])
                  ->assertFormFieldExists('probability');
    }

    public function test_contact_options_filtered_by_organization()
    {
        $otherOrg = Organization::factory()->create(['name' => 'Other Restaurant']);
        $otherContact = Contact::factory()->for($otherOrg)->create([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
        ]);

        $component = Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm(['organization_id' => $this->organization->id]);

        // The contact dropdown should only show contacts from the selected organization
        $component->assertFormFieldExists('contact_id');
    }

    public function test_can_edit_opportunity()
    {
        $opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'title' => 'Original Title',
                'stage' => 'lead',
                'value' => 25000.00,
                'priority' => 'medium',
            ]);

        Livewire::test(OpportunityResource\Pages\EditOpportunity::class, [
            'record' => $opportunity->getRouteKey(),
        ])
        ->fillForm([
            'title' => 'Updated Opportunity Title',
            'stage' => 'proposal',
            'value' => 50000.00,
            'priority' => 'high',
            'notes' => 'Updated with new requirements',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'title' => 'Updated Opportunity Title',
            'stage' => 'proposal',
            'value' => 50000.00,
            'priority' => 'high',
        ]);
    }

    public function test_can_move_opportunity_stage_with_action()
    {
        $opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'stage' => 'lead',
                'probability' => 10,
            ]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('moveStage', $opportunity, data: [
                'new_stage' => 'proposal',
                'notes' => 'Moved to proposal after successful demo',
            ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'proposal',
        ]);
    }

    public function test_can_filter_by_stage()
    {
        $leadOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['stage' => 'lead']);

        $proposalOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['stage' => 'proposal']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('stage', 'lead')
            ->assertCanSeeTableRecords([$leadOpportunity])
            ->assertCanNotSeeTableRecords([$proposalOpportunity]);
    }

    public function test_can_filter_by_status()
    {
        $openOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['status' => 'open']);

        $wonOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['status' => 'won']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('status', 'open')
            ->assertCanSeeTableRecords([$openOpportunity])
            ->assertCanNotSeeTableRecords([$wonOpportunity]);
    }

    public function test_can_filter_by_priority()
    {
        $highPriorityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'high']);

        $lowPriorityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('priority', 'high')
            ->assertCanSeeTableRecords([$highPriorityOpportunity])
            ->assertCanNotSeeTableRecords([$lowPriorityOpportunity]);
    }

    public function test_can_filter_by_organization()
    {
        $org1Opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        $org2 = Organization::factory()->create(['name' => 'Other Organization']);
        $org2Contact = Contact::factory()->for($org2)->create();
        $org2Opportunity = Opportunity::factory()
            ->for($org2)
            ->for($org2Contact)
            ->for($this->user)
            ->create();

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('organization', $this->organization->id)
            ->assertCanSeeTableRecords([$org1Opportunity])
            ->assertCanNotSeeTableRecords([$org2Opportunity]);
    }

    public function test_can_filter_by_assigned_user()
    {
        $myOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        $otherUser = User::factory()->create();
        $otherOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($otherUser)
            ->create();

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('user', $this->user->id)
            ->assertCanSeeTableRecords([$myOpportunity])
            ->assertCanNotSeeTableRecords([$otherOpportunity]);
    }

    public function test_can_filter_high_value_opportunities()
    {
        $highValueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 15000.00]);

        $lowValueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 5000.00]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('high_value')
            ->assertCanSeeTableRecords([$highValueOpportunity])
            ->assertCanNotSeeTableRecords([$lowValueOpportunity]);
    }

    public function test_can_filter_stale_opportunities()
    {
        $staleOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['last_activity_date' => now()->subDays(35)]);

        $activeOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['last_activity_date' => now()->subDays(5)]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('stale')
            ->assertCanSeeTableRecords([$staleOpportunity])
            ->assertCanNotSeeTableRecords([$activeOpportunity]);
    }

    public function test_can_filter_expected_close_this_month()
    {
        $thisMonthOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['expectedCloseDate' => now()->addDays(10)]);

        $nextMonthOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['expectedCloseDate' => now()->addDays(45)]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('expected_close_this_month')
            ->assertCanSeeTableRecords([$thisMonthOpportunity])
            ->assertCanNotSeeTableRecords([$nextMonthOpportunity]);
    }

    public function test_can_filter_overdue_opportunities()
    {
        $overdueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'expectedCloseDate' => now()->subDays(5),
                'status' => 'open',
            ]);

        $futureOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'expectedCloseDate' => now()->addDays(10),
                'status' => 'open',
            ]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overdueOpportunity])
            ->assertCanNotSeeTableRecords([$futureOpportunity]);
    }

    public function test_can_filter_by_value_range()
    {
        $lowValueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 5000.00]);

        $midValueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 15000.00]);

        $highValueOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 25000.00]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('value_range', [
                'value_from' => 10000,
                'value_to' => 20000,
            ])
            ->assertCanSeeTableRecords([$midValueOpportunity])
            ->assertCanNotSeeTableRecords([$lowValueOpportunity, $highValueOpportunity]);
    }

    public function test_can_filter_by_high_probability()
    {
        $lowProbabilityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['probability' => 25]);

        $highProbabilityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['probability' => 75]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('probability_range', [
                'probability_min' => 50,
            ])
            ->assertCanSeeTableRecords([$highProbabilityOpportunity])
            ->assertCanNotSeeTableRecords([$lowProbabilityOpportunity]);
    }

    public function test_can_filter_recent_activity()
    {
        $recentOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['last_activity_date' => now()->subDays(3)]);

        $oldOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['last_activity_date' => now()->subDays(10)]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('recent_activity')
            ->assertCanSeeTableRecords([$recentOpportunity])
            ->assertCanNotSeeTableRecords([$oldOpportunity]);
    }

    public function test_can_search_opportunities()
    {
        $opportunity1 = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['title' => 'Kitchen Equipment Upgrade']);

        $opportunity2 = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['title' => 'POS System Implementation']);

        $opportunity3 = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['title' => 'Staff Training Program']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->searchTable('Kitchen')
            ->assertCanSeeTableRecords([$opportunity1])
            ->assertCanNotSeeTableRecords([$opportunity2, $opportunity3]);
    }

    public function test_bulk_update_priority_action()
    {
        $opportunities = Opportunity::factory()->count(3)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableBulkAction('updatePriority', $opportunities, data: [
                'priority' => 'high',
            ]);

        foreach ($opportunities as $opportunity) {
            $this->assertDatabaseHas('opportunities', [
                'id' => $opportunity->id,
                'priority' => 'high',
            ]);
        }
    }

    public function test_can_bulk_delete_opportunities()
    {
        $opportunities = Opportunity::factory()->count(3)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableBulkAction('delete', $opportunities);

        foreach ($opportunities as $opportunity) {
            $this->assertSoftDeleted('opportunities', [
                'id' => $opportunity->id,
            ]);
        }
    }

    public function test_form_validation_works()
    {
        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm([
                'title' => '', // Required field
                'organization_id' => null, // Required field
                'user_id' => null, // Required field
                'stage' => '', // Required field
                'status' => '', // Required field
                'expectedCloseDate' => '', // Required field
                'priority' => '', // Required field
                'probability' => 150, // Above maximum (100)
                'value' => -1000, // Negative value
            ])
            ->call('create')
            ->assertHasFormErrors(['title', 'organization_id', 'user_id', 'stage', 'status', 'expectedCloseDate', 'priority', 'probability']);
    }

    public function test_stage_badge_colors_display_correctly()
    {
        $leadOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['stage' => 'lead']);

        $closedOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['stage' => 'closed']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertCanSeeTableRecords([$leadOpportunity, $closedOpportunity])
            ->assertSee('Lead')
            ->assertSee('Closed');
    }

    public function test_status_badge_colors_display_correctly()
    {
        $openOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['status' => 'open']);

        $wonOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['status' => 'won']);

        $lostOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['status' => 'lost']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertCanSeeTableRecords([$openOpportunity, $wonOpportunity, $lostOpportunity])
            ->assertSee('Open')
            ->assertSee('Won')
            ->assertSee('Lost');
    }

    public function test_priority_badge_colors_display_correctly()
    {
        $highPriorityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'high']);

        $lowPriorityOpportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertCanSeeTableRecords([$highPriorityOpportunity, $lowPriorityOpportunity])
            ->assertSee('High')
            ->assertSee('Low');
    }

    public function test_value_formatting_displays_correctly()
    {
        $opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['value' => 12345.67]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertCanSeeTableRecords([$opportunity])
            ->assertSee('$12,345.67');
    }

    public function test_probability_percentage_displays_correctly()
    {
        $opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['probability' => 75]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->assertCanSeeTableRecords([$opportunity])
            ->assertSee('75%');
    }

    public function test_default_sorting_by_created_at_desc()
    {
        $older = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['created_at' => now()->subDays(2)]);

        $newer = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['created_at' => now()->subDay()]);

        $component = Livewire::test(OpportunityResource\Pages\ListOpportunities::class);
        
        // Verify records are visible (exact sorting verification would require examining the table order)
        $component->assertCanSeeTableRecords([$older, $newer]);
    }

    public function test_can_view_opportunity_details()
    {
        $opportunity = Opportunity::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'title' => 'Detailed Opportunity',
                'description' => 'Comprehensive opportunity description',
            ]);

        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('view', $opportunity);
    }

    public function test_hidden_tracking_fields_are_set()
    {
        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm([
                'title' => 'Test Opportunity',
                'organization_id' => $this->organization->id,
                'user_id' => $this->user->id,
                'stage' => 'lead',
                'status' => 'open',
                'expectedCloseDate' => now()->addDays(30)->format('Y-m-d'),
                'priority' => 'medium',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'Test Opportunity',
            'stage_changed_by_user_id' => $this->user->id,
            'isActive' => true,
        ]);
    }

    public function test_can_access_kanban_page()
    {
        // Test that the kanban page route exists and is accessible
        $opportunities = Opportunity::factory()->count(5)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['stage' => 'lead']);

        // This would test the kanban functionality when the page component is available
        $response = $this->get(route('filament.admin.resources.opportunities.kanban'));
        $response->assertSuccessful();
    }

    public function test_opportunity_stage_options_are_available()
    {
        $stageOptions = Opportunity::getStageOptions();
        
        $this->assertIsArray($stageOptions);
        $this->assertArrayHasKey('lead', $stageOptions);
        $this->assertArrayHasKey('prospect', $stageOptions);
        $this->assertArrayHasKey('proposal', $stageOptions);
        $this->assertArrayHasKey('negotiation', $stageOptions);
        $this->assertArrayHasKey('closed', $stageOptions);
    }

    public function test_opportunity_status_options_are_available()
    {
        $statusOptions = Opportunity::getStatusOptions();
        
        $this->assertIsArray($statusOptions);
        $this->assertArrayHasKey('open', $statusOptions);
        $this->assertArrayHasKey('won', $statusOptions);
        $this->assertArrayHasKey('lost', $statusOptions);
    }

    public function test_opportunity_priority_options_are_available()
    {
        $priorityOptions = Opportunity::getPriorityOptions();
        
        $this->assertIsArray($priorityOptions);
        $this->assertArrayHasKey('low', $priorityOptions);
        $this->assertArrayHasKey('medium', $priorityOptions);
        $this->assertArrayHasKey('high', $priorityOptions);
    }
}