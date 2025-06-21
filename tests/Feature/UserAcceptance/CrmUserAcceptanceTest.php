<?php

namespace Tests\Feature\UserAcceptance;

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
 * User Acceptance Tests for PantryCRM
 * 
 * These tests validate that the CRM system meets the business requirements
 * from the perspective of actual users (sales representatives, managers, etc.)
 */
class CrmUserAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesRep;
    protected User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->salesRep = User::factory()->create([
            'name' => 'Jane Sales Rep',
            'email' => 'jane@pantrysolutions.com',
        ]);
        
        $this->salesManager = User::factory()->create([
            'name' => 'Bob Sales Manager',
            'email' => 'bob@pantrysolutions.com',
        ]);
    }

    /**
     * UAT-001: As a sales rep, I want to quickly add a new restaurant prospect
     * so that I can start tracking our sales relationship.
     */
    public function test_sales_rep_can_quickly_add_new_restaurant_prospect()
    {
        $this->actingAs($this->salesRep);

        // User Story: Sales rep receives a lead from a trade show
        // Acceptance Criteria: Can create organization in under 2 minutes with essential info
        
        $restaurantData = [
            'name' => 'Artisan Pizzeria',
            'type' => 'PROSPECT',
            'priority' => 'B',
            'segment' => 'CASUAL_DINING',
            'status' => 'ACTIVE',
            'city' => 'Portland',
            'state' => 'OR',
            'phone' => '503-555-0123',
            'email' => 'info@artisanpizzeria.com',
            'primaryContact' => 'Tony Romano',
            'notes' => 'Met at NRA trade show. Interested in new pizza oven technology.',
        ];

        $component = Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm($restaurantData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify the prospect was created correctly
        $this->assertDatabaseHas('organizations', [
            'name' => 'Artisan Pizzeria',
            'type' => 'PROSPECT',
            'priority' => 'B',
            'primaryContact' => 'Tony Romano',
        ]);

        // User should be able to see the new prospect in the list
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->assertSuccessful()
            ->assertSee('Artisan Pizzeria')
            ->assertSee('Tony Romano');
    }

    /**
     * UAT-002: As a sales rep, I want to log customer interactions quickly
     * so that I can maintain accurate records without spending too much admin time.
     */
    public function test_sales_rep_can_log_interactions_efficiently()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create organization and contact
        $organization = Organization::factory()->create(['name' => 'Quick Service Cafe']);
        $contact = Contact::factory()->for($organization)->create(['firstName' => 'Maria', 'lastName' => 'Manager']);

        // User Story: Sales rep just finished a phone call and needs to log it quickly
        // Acceptance Criteria: Can log interaction in under 30 seconds with key details
        
        $interactionData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'CALL',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 15,
            'subject' => 'Follow-up on equipment quote',
            'notes' => 'Customer ready to move forward. Waiting for approval from district manager.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'follow_up_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($interactionData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify interaction was logged with proper details
        $this->assertDatabaseHas('interactions', [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'subject' => 'Follow-up on equipment quote',
            'outcome' => 'POSITIVE',
            'user_id' => $this->salesRep->id,
        ]);

        // User should see the interaction in their activity list
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('my_interactions')
            ->assertSee('Follow-up on equipment quote')
            ->assertSee('Quick Service Cafe');
    }

    /**
     * UAT-003: As a sales rep, I want to track opportunities through the sales pipeline
     * so that I can focus on deals most likely to close.
     */
    public function test_sales_rep_can_manage_sales_pipeline_effectively()
    {
        $this->actingAs($this->salesRep);

        // Setup
        $organization = Organization::factory()->create(['name' => 'Premium Steakhouse']);
        $contact = Contact::factory()->for($organization)->create(['firstName' => 'Chef', 'lastName' => 'Williams']);

        // User Story: Sales rep wants to create and track a high-value opportunity
        // Acceptance Criteria: Can create opportunity, move through stages, and track progress
        
        $opportunityData = [
            'title' => 'High-End Kitchen Renovation',
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'user_id' => $this->salesRep->id,
            'description' => 'Complete kitchen renovation with premium equipment package',
            'stage' => 'lead',
            'probability' => 25,
            'status' => 'open',
            'value' => 125000.00,
            'expectedCloseDate' => now()->addDays(60)->format('Y-m-d'),
            'priority' => 'high',
            'source' => 'Referral',
            'lead_score' => 85,
        ];

        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm($opportunityData)
            ->call('create')
            ->assertHasNoFormErrors();

        $opportunity = Opportunity::where('title', 'High-End Kitchen Renovation')->first();

        // Move opportunity through pipeline stages
        $stages = ['prospect', 'proposal', 'negotiation'];
        foreach ($stages as $stage) {
            Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
                ->callTableAction('moveStage', $opportunity, data: [
                    'new_stage' => $stage,
                    'notes' => "Progressing to {$stage} stage",
                ]);

            $opportunity->refresh();
            $this->assertEquals($stage, $opportunity->stage);
        }

        // Verify opportunity progression is tracked
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'negotiation',
            'stage_changed_by_user_id' => $this->salesRep->id,
        ]);

        // User should be able to filter and find their high-priority opportunities
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('priority', 'high')
            ->filterTable('user', $this->salesRep->id)
            ->assertSee('High-End Kitchen Renovation')
            ->assertSee('$125,000.00');
    }

    /**
     * UAT-004: As a sales manager, I want to see which deals are at risk
     * so that I can provide support to close them.
     */
    public function test_sales_manager_can_identify_at_risk_opportunities()
    {
        $this->actingAs($this->salesManager);

        // Setup: Create opportunities in various states
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        // At-risk opportunity: overdue expected close date
        $overdueOpportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'title' => 'Overdue Equipment Deal',
                'expectedCloseDate' => now()->subDays(10),
                'status' => 'open',
                'stage' => 'negotiation',
                'value' => 50000,
            ]);

        // Stale opportunity: no recent activity
        $staleOpportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'title' => 'Stale Opportunity',
                'last_activity_date' => now()->subDays(35),
                'status' => 'open',
                'stage' => 'proposal',
                'value' => 30000,
            ]);

        // Healthy opportunity
        $healthyOpportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'title' => 'Healthy Deal',
                'expectedCloseDate' => now()->addDays(15),
                'last_activity_date' => now()->subDays(2),
                'status' => 'open',
                'stage' => 'proposal',
                'value' => 75000,
            ]);

        // Manager can identify overdue opportunities
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overdueOpportunity])
            ->assertCanNotSeeTableRecords([$healthyOpportunity]);

        // Manager can identify stale opportunities
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('stale')
            ->assertCanSeeTableRecords([$staleOpportunity])
            ->assertCanNotSeeTableRecords([$healthyOpportunity]);

        // Manager can see high-value opportunities
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('high_value')
            ->assertCanSeeTableRecords([$overdueOpportunity, $healthyOpportunity])
            ->assertCanNotSeeTableRecords([$staleOpportunity]);
    }

    /**
     * UAT-005: As a sales rep, I want to convert a prospect to a client
     * so that I can track our ongoing relationship.
     */
    public function test_sales_rep_can_convert_prospect_to_client()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create a prospect organization
        $prospect = Organization::factory()->create([
            'name' => 'New Restaurant Venture',
            'type' => 'PROSPECT',
            'priority' => 'A',
            'status' => 'ACTIVE',
        ]);

        $contact = Contact::factory()->for($prospect)->create();

        // Create and close a won opportunity
        $opportunity = Opportunity::factory()
            ->for($prospect)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'title' => 'Initial Equipment Package',
                'stage' => 'negotiation',
                'status' => 'open',
                'value' => 45000,
            ]);

        // Close the deal
        Livewire::test(OpportunityResource\Pages\EditOpportunity::class, [
            'record' => $opportunity->getRouteKey(),
        ])
        ->fillForm([
            'stage' => 'closed',
            'status' => 'won',
            'probability' => 100,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        // Convert prospect to client
        Livewire::test(OrganizationResource\Pages\EditOrganization::class, [
            'record' => $prospect->getRouteKey(),
        ])
        ->fillForm([
            'type' => 'CLIENT',
            'notes' => 'Converted to client after successful equipment sale',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        // Verify conversion
        $prospect->refresh();
        $this->assertEquals('CLIENT', $prospect->type);

        $opportunity->refresh();
        $this->assertEquals('won', $opportunity->status);
        $this->assertEquals('closed', $opportunity->stage);

        // User should be able to see the new client
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->filterTable('type', 'CLIENT')
            ->assertSee('New Restaurant Venture');
    }

    /**
     * UAT-006: As a sales rep, I want to manage multiple contacts per organization
     * so that I can maintain relationships with all decision makers.
     */
    public function test_sales_rep_can_manage_multiple_contacts_per_organization()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create organization
        $organization = Organization::factory()->create(['name' => 'Multi-Location Chain']);

        // Add multiple contacts with different roles
        $ceo = Contact::factory()->for($organization)->create([
            'firstName' => 'Sarah',
            'lastName' => 'Johnson',
            'position' => 'CEO',
            'isPrimary' => true,
            'email' => 'sarah@chain.com',
        ]);

        $cfo = Contact::factory()->for($organization)->create([
            'firstName' => 'Mike',
            'lastName' => 'Chen',
            'position' => 'CFO',
            'isPrimary' => false,
            'email' => 'mike@chain.com',
        ]);

        $operations = Contact::factory()->for($organization)->create([
            'firstName' => 'Lisa',
            'lastName' => 'Rodriguez',
            'position' => 'VP Operations',
            'isPrimary' => false,
            'email' => 'lisa@chain.com',
        ]);

        // Verify all contacts are associated with the organization
        $this->assertDatabaseCount('contacts', 3);
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $organization->id,
            'firstName' => 'Sarah',
            'isPrimary' => true,
        ]);

        // User can filter to see only primary contacts
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('primary_contacts')
            ->assertSee('Sarah Johnson')
            ->assertDontSee('Mike Chen')
            ->assertDontSee('Lisa Rodriguez');

        // User can filter by organization
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('organization', [$organization->id])
            ->assertSee('Sarah Johnson')
            ->assertSee('Mike Chen')
            ->assertSee('Lisa Rodriguez');

        // User can log interactions with different contacts
        $ceoInteraction = [
            'organization_id' => $organization->id,
            'contact_id' => $ceo->id,
            'type' => 'MEETING',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'subject' => 'Strategic discussion with CEO',
            'outcome' => 'POSITIVE',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($ceoInteraction)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactions', [
            'contact_id' => $ceo->id,
            'subject' => 'Strategic discussion with CEO',
        ]);
    }

    /**
     * UAT-007: As a sales rep, I want to prioritize my follow-ups
     * so that I don't miss important opportunities.
     */
    public function test_sales_rep_can_prioritize_follow_ups()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create interactions requiring follow-up
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        // High priority follow-up (overdue)
        $overdueInteraction = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'Urgent proposal follow-up',
                'outcome' => 'FOLLOWUPNEEDED',
                'follow_up_date' => now()->subDays(2),
                'priority' => 'high',
            ]);

        // Medium priority follow-up (due today)
        $todayInteraction = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'Contract discussion',
                'outcome' => 'FOLLOWUPNEEDED',
                'follow_up_date' => now(),
                'priority' => 'medium',
            ]);

        // Future follow-up
        $futureInteraction = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'Check-in call',
                'outcome' => 'FOLLOWUPNEEDED',
                'follow_up_date' => now()->addDays(7),
                'priority' => 'low',
            ]);

        // User can filter for follow-ups needed
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('follow_up_required')
            ->assertCanSeeTableRecords([$overdueInteraction, $todayInteraction, $futureInteraction]);

        // User can filter by priority
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('priority', ['high'])
            ->assertCanSeeTableRecords([$overdueInteraction])
            ->assertCanNotSeeTableRecords([$todayInteraction, $futureInteraction]);

        // User can see their own interactions
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('my_interactions')
            ->assertCanSeeTableRecords([$overdueInteraction, $todayInteraction, $futureInteraction]);
    }

    /**
     * UAT-008: As a sales manager, I want to see team performance metrics
     * so that I can identify coaching opportunities.
     */
    public function test_sales_manager_can_monitor_team_performance()
    {
        $this->actingAs($this->salesManager);

        // Setup: Create opportunities for different sales reps
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        $salesRep2 = User::factory()->create(['name' => 'Tom Sales Rep']);

        // High performer: multiple won deals
        $wonDeals = Opportunity::factory()->count(3)
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'status' => 'won',
                'stage' => 'closed',
                'value' => 25000,
            ]);

        // Underperformer: mostly lost deals
        $lostDeals = Opportunity::factory()->count(2)
            ->for($organization)
            ->for($contact)
            ->for($salesRep2)
            ->create([
                'status' => 'lost',
                'stage' => 'closed',
                'value' => 15000,
            ]);

        // Active pipeline
        $activeDeal = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'status' => 'open',
                'stage' => 'proposal',
                'value' => 50000,
            ]);

        // Manager can see won opportunities by rep
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('status', 'won')
            ->filterTable('user', $this->salesRep->id)
            ->assertCanSeeTableRecords($wonDeals->toArray())
            ->assertCanNotSeeTableRecords($lostDeals->toArray());

        // Manager can see lost opportunities by rep
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('status', 'lost')
            ->filterTable('user', $salesRep2->id)
            ->assertCanSeeTableRecords($lostDeals->toArray());

        // Manager can see high-value deals in pipeline
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('high_value')
            ->filterTable('status', 'open')
            ->assertCanSeeTableRecords([$activeDeal]);
    }

    /**
     * UAT-009: As a sales rep, I want to quickly find customer information
     * so that I can prepare for calls and meetings.
     */
    public function test_sales_rep_can_quickly_find_customer_information()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create test data
        $italianRestaurant = Organization::factory()->create([
            'name' => 'Nonna\'s Italian Kitchen',
            'segment' => 'FINE_DINING',
            'email' => 'info@nonnas.com',
        ]);

        $sushiRestaurant = Organization::factory()->create([
            'name' => 'Tokyo Sushi Bar',
            'segment' => 'FINE_DINING',
            'email' => 'contact@tokyosushi.com',
        ]);

        $contact1 = Contact::factory()->for($italianRestaurant)->create([
            'firstName' => 'Giuseppe',
            'lastName' => 'Rossi',
            'email' => 'giuseppe@nonnas.com',
        ]);

        $contact2 = Contact::factory()->for($sushiRestaurant)->create([
            'firstName' => 'Yuki',
            'lastName' => 'Tanaka',
            'email' => 'yuki@tokyosushi.com',
        ]);

        // User can search organizations by name
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('Italian')
            ->assertSee('Nonna\'s Italian Kitchen')
            ->assertDontSee('Tokyo Sushi Bar');

        // User can search organizations by email
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('nonnas.com')
            ->assertSee('Nonna\'s Italian Kitchen')
            ->assertDontSee('Tokyo Sushi Bar');

        // User can search contacts by name
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->searchTable('Giuseppe')
            ->assertSee('Giuseppe Rossi')
            ->assertDontSee('Yuki Tanaka');

        // User can search contacts by email
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->searchTable('yuki@tokyosushi.com')
            ->assertSee('Yuki Tanaka')
            ->assertDontSee('Giuseppe Rossi');

        // User can filter by segment
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('FINE_DINING')
            ->assertSee('Nonna\'s Italian Kitchen')
            ->assertSee('Tokyo Sushi Bar');
    }

    /**
     * UAT-010: As a sales rep, I want to track interaction history per customer
     * so that I can maintain context in my relationships.
     */
    public function test_sales_rep_can_view_complete_customer_interaction_history()
    {
        $this->actingAs($this->salesRep);

        // Setup: Create customer with interaction history
        $organization = Organization::factory()->create(['name' => 'Established Restaurant']);
        $contact = Contact::factory()->for($organization)->create();

        // Create interaction history over time
        $initialCall = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'Initial contact call',
                'type' => 'CALL',
                'interactionDate' => now()->subDays(30),
                'outcome' => 'POSITIVE',
            ]);

        $siteVisit = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'On-site needs assessment',
                'type' => 'VISIT',
                'interactionDate' => now()->subDays(20),
                'outcome' => 'POSITIVE',
            ]);

        $proposalMeeting = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->salesRep)
            ->create([
                'subject' => 'Proposal presentation',
                'type' => 'MEETING',
                'interactionDate' => now()->subDays(10),
                'outcome' => 'FOLLOWUPNEEDED',
            ]);

        // User can see all interactions for the organization
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('organization', [$organization->id])
            ->assertSee('Initial contact call')
            ->assertSee('On-site needs assessment')
            ->assertSee('Proposal presentation');

        // User can see chronological order (most recent first by default)
        $component = Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('organization', [$organization->id]);

        // Verify all interactions are visible
        $component->assertCanSeeTableRecords([$initialCall, $siteVisit, $proposalMeeting]);

        // User can filter by interaction type
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('organization', [$organization->id])
            ->filterTable('type', ['VISIT'])
            ->assertSee('On-site needs assessment')
            ->assertDontSee('Initial contact call');

        // User can filter by outcome
        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('organization', [$organization->id])
            ->filterTable('outcome', ['POSITIVE'])
            ->assertSee('Initial contact call')
            ->assertSee('On-site needs assessment')
            ->assertDontSee('Proposal presentation');
    }
}