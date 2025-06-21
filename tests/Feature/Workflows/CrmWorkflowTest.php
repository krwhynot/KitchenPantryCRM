<?php

namespace Tests\Feature\Workflows;

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

class CrmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test Sales Rep',
            'email' => 'sales@test.com',
        ]);
        $this->actingAs($this->user);
    }

    public function test_complete_crm_workflow_from_prospect_to_closed_deal()
    {
        // Step 1: Create a new prospect organization
        $organizationData = [
            'name' => 'Gourmet Bistro',
            'type' => 'PROSPECT',
            'priority' => 'A',
            'segment' => 'FINE_DINING',
            'status' => 'ACTIVE',
            'address' => '456 Culinary Avenue',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zipCode' => '94102',
            'phone' => '+1-415-555-0199',
            'email' => 'contact@gourmetbistro.com',
            'website' => 'www.gourmetbistro.com',
            'estimatedRevenue' => 750000,
            'employeeCount' => 30,
            'primaryContact' => 'Chef Maria Rodriguez',
            'notes' => 'High-end restaurant with focus on local ingredients',
        ];

        Livewire::test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm($organizationData)
            ->call('create')
            ->assertHasNoFormErrors();

        $organization = Organization::where('name', 'Gourmet Bistro')->first();
        $this->assertNotNull($organization);
        
        // Step 2: Add primary contact to the organization
        $contactData = [
            'firstName' => 'Maria',
            'lastName' => 'Rodriguez',
            'email' => 'maria@gourmetbistro.com',
            'phone' => '+1-415-555-0200',
            'organization_id' => $organization->id,
            'position' => 'Head Chef & Owner',
            'isPrimary' => true,
            'notes' => 'Decision maker for kitchen equipment purchases',
        ];

        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm($contactData)
            ->call('create')
            ->assertHasNoFormErrors();

        $contact = Contact::where('email', 'maria@gourmetbistro.com')->first();
        $this->assertNotNull($contact);
        $this->assertEquals($organization->id, $contact->organization_id);

        // Step 3: Log initial interaction (cold call)
        $interactionData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'CALL',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 20,
            'subject' => 'Initial cold call - kitchen equipment needs',
            'notes' => 'Interested in upgrading their aging equipment. Mentioned need for new ovens and prep stations.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'follow_up_date' => now()->addDays(3)->format('Y-m-d'),
            'nextAction' => 'Schedule on-site visit for needs assessment',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($interactionData)
            ->call('create')
            ->assertHasNoFormErrors();

        $interaction = Interaction::where('subject', 'Initial cold call - kitchen equipment needs')->first();
        $this->assertNotNull($interaction);
        $this->assertEquals('POSITIVE', $interaction->outcome);

        // Step 4: Create opportunity based on the interaction
        $opportunityData = [
            'title' => 'Kitchen Equipment Modernization Project',
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'user_id' => $this->user->id,
            'description' => 'Complete kitchen equipment upgrade including ovens, prep stations, and refrigeration units',
            'stage' => 'lead',
            'probability' => 25,
            'status' => 'open',
            'value' => 85000.00,
            'expectedCloseDate' => now()->addDays(45)->format('Y-m-d'),
            'priority' => 'high',
            'source' => 'Cold Call',
            'lead_score' => 75,
            'next_action' => 'Schedule equipment demonstration',
            'notes' => 'High-value prospect with immediate need for equipment upgrade',
        ];

        Livewire::test(OpportunityResource\Pages\CreateOpportunity::class)
            ->fillForm($opportunityData)
            ->call('create')
            ->assertHasNoFormErrors();

        $opportunity = Opportunity::where('title', 'Kitchen Equipment Modernization Project')->first();
        $this->assertNotNull($opportunity);
        $this->assertEquals('lead', $opportunity->stage);
        $this->assertEquals(85000.00, $opportunity->value);

        // Step 5: Log follow-up interaction (site visit)
        $siteVisitData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'VISIT',
            'interactionDate' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration' => 120,
            'subject' => 'On-site kitchen assessment and needs analysis',
            'notes' => 'Conducted thorough assessment. Confirmed need for 2 convection ovens, prep station, and walk-in cooler upgrade. Chef Maria very engaged.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'follow_up_date' => now()->addDays(7)->format('Y-m-d'),
            'nextAction' => 'Prepare detailed proposal with equipment specifications',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($siteVisitData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Step 6: Move opportunity to prospect stage
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('moveStage', $opportunity, data: [
                'new_stage' => 'prospect',
                'notes' => 'Moved to prospect after successful site visit and needs assessment',
            ]);

        $opportunity->refresh();
        $this->assertEquals('prospect', $opportunity->stage);

        // Step 7: Log proposal presentation interaction
        $proposalData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'MEETING',
            'interactionDate' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'duration' => 90,
            'subject' => 'Equipment proposal presentation',
            'notes' => 'Presented comprehensive proposal. Chef Maria impressed with energy efficiency features. Discussed financing options.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'follow_up_date' => now()->addDays(5)->format('Y-m-d'),
            'nextAction' => 'Follow up on decision timeline and address any questions',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($proposalData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Step 8: Move opportunity to proposal stage
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('moveStage', $opportunity, data: [
                'new_stage' => 'proposal',
                'notes' => 'Proposal submitted and well received',
            ]);

        $opportunity->refresh();
        $this->assertEquals('proposal', $opportunity->stage);

        // Step 9: Log negotiation interaction
        $negotiationData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'CALL',
            'interactionDate' => now()->addDays(15)->format('Y-m-d H:i:s'),
            'duration' => 45,
            'subject' => 'Contract negotiation and pricing discussion',
            'notes' => 'Negotiated final pricing. Agreed on installation timeline. Chef Maria ready to move forward.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'nextAction' => 'Send final contract for signature',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($negotiationData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Step 10: Move opportunity to negotiation stage
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('moveStage', $opportunity, data: [
                'new_stage' => 'negotiation',
                'notes' => 'In final contract negotiation phase',
            ]);

        $opportunity->refresh();
        $this->assertEquals('negotiation', $opportunity->stage);

        // Step 11: Update organization status to CLIENT
        Livewire::test(OrganizationResource\Pages\EditOrganization::class, [
            'record' => $organization->getRouteKey(),
        ])
        ->fillForm([
            'type' => 'CLIENT',
            'status' => 'ACTIVE',
            'notes' => 'Converted from prospect to client after successful equipment sale',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $organization->refresh();
        $this->assertEquals('CLIENT', $organization->type);

        // Step 12: Log contract signed interaction and close opportunity
        $contractSignedData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'EMAIL',
            'interactionDate' => now()->addDays(18)->format('Y-m-d H:i:s'),
            'duration' => 5,
            'subject' => 'Contract signed - equipment order confirmed',
            'notes' => 'Received signed contract. Equipment order processed. Installation scheduled for next month.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'nextAction' => 'Coordinate installation scheduling with operations team',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($contractSignedData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Step 13: Close opportunity as won
        Livewire::test(OpportunityResource\Pages\EditOpportunity::class, [
            'record' => $opportunity->getRouteKey(),
        ])
        ->fillForm([
            'stage' => 'closed',
            'status' => 'won',
            'probability' => 100,
            'notes' => 'Deal closed successfully. Contract signed for $85,000 equipment package.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $opportunity->refresh();
        $this->assertEquals('closed', $opportunity->stage);
        $this->assertEquals('won', $opportunity->status);
        $this->assertEquals(100, $opportunity->probability);

        // Verify the complete workflow data integrity
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Gourmet Bistro',
            'type' => 'CLIENT',
            'priority' => 'A',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'organization_id' => $organization->id,
            'isPrimary' => true,
        ]);

        $this->assertDatabaseCount('interactions', 5); // All interaction logs
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed',
            'status' => 'won',
            'value' => 85000.00,
        ]);
    }

    public function test_lost_opportunity_workflow()
    {
        // Create organization, contact, and opportunity
        $organization = Organization::factory()->create([
            'name' => 'Budget Diner',
            'type' => 'PROSPECT',
            'priority' => 'C',
        ]);

        $contact = Contact::factory()->for($organization)->create([
            'firstName' => 'Bob',
            'lastName' => 'Manager',
            'isPrimary' => true,
        ]);

        $opportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->user)
            ->create([
                'title' => 'Basic Equipment Package',
                'stage' => 'proposal',
                'status' => 'open',
                'value' => 15000.00,
            ]);

        // Log negative interaction
        $rejectionData = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'CALL',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 15,
            'subject' => 'Customer decided not to proceed',
            'notes' => 'Budget constraints and timing not right. Will reconsider in 6 months.',
            'outcome' => 'NEGATIVE',
            'priority' => 'low',
            'follow_up_date' => now()->addDays(180)->format('Y-m-d'),
            'nextAction' => 'Follow up in 6 months when budget cycle resets',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($rejectionData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Close opportunity as lost
        Livewire::test(OpportunityResource\Pages\EditOpportunity::class, [
            'record' => $opportunity->getRouteKey(),
        ])
        ->fillForm([
            'status' => 'lost',
            'probability' => 0,
            'notes' => 'Lost due to budget constraints. Potential future opportunity.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $opportunity->refresh();
        $this->assertEquals('lost', $opportunity->status);
        $this->assertEquals(0, $opportunity->probability);

        // Verify the data
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'status' => 'lost',
            'probability' => 0,
        ]);

        $this->assertDatabaseHas('interactions', [
            'organization_id' => $organization->id,
            'outcome' => 'NEGATIVE',
            'subject' => 'Customer decided not to proceed',
        ]);
    }

    public function test_multi_contact_opportunity_workflow()
    {
        // Create organization with multiple contacts
        $organization = Organization::factory()->create([
            'name' => 'Restaurant Chain HQ',
            'type' => 'PROSPECT',
            'priority' => 'A',
        ]);

        $primaryContact = Contact::factory()->for($organization)->create([
            'firstName' => 'Sarah',
            'lastName' => 'CEO',
            'position' => 'Chief Executive Officer',
            'isPrimary' => true,
        ]);

        $secondaryContact = Contact::factory()->for($organization)->create([
            'firstName' => 'Mike',
            'lastName' => 'CFO',
            'position' => 'Chief Financial Officer',
            'isPrimary' => false,
        ]);

        $operationsContact = Contact::factory()->for($organization)->create([
            'firstName' => 'Lisa',
            'lastName' => 'Operations',
            'position' => 'VP of Operations',
            'isPrimary' => false,
        ]);

        // Create opportunity
        $opportunity = Opportunity::factory()
            ->for($organization)
            ->for($primaryContact)
            ->for($this->user)
            ->create([
                'title' => 'Multi-Location Equipment Rollout',
                'stage' => 'lead',
                'value' => 250000.00,
                'priority' => 'high',
            ]);

        // Log interaction with CEO (primary contact)
        $ceoInteractionData = [
            'organization_id' => $organization->id,
            'contact_id' => $primaryContact->id,
            'type' => 'MEETING',
            'interactionDate' => now()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'subject' => 'Strategic discussion with CEO',
            'notes' => 'CEO interested in standardizing equipment across all 5 locations. Wants to discuss ROI.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($ceoInteractionData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Log interaction with CFO
        $cfoInteractionData = [
            'organization_id' => $organization->id,
            'contact_id' => $secondaryContact->id,
            'type' => 'CALL',
            'interactionDate' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration' => 45,
            'subject' => 'Financial analysis discussion with CFO',
            'notes' => 'CFO wants detailed ROI analysis and financing options. Concerned about upfront costs.',
            'outcome' => 'NEUTRAL',
            'priority' => 'high',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($cfoInteractionData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Log interaction with Operations VP
        $opsInteractionData = [
            'organization_id' => $organization->id,
            'contact_id' => $operationsContact->id,
            'type' => 'VISIT',
            'interactionDate' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'duration' => 180,
            'subject' => 'Technical requirements review with Operations',
            'notes' => 'Reviewed technical specs for all locations. Operations VP supportive and provided detailed requirements.',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
        ];

        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm($opsInteractionData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Move opportunity through stages
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableAction('moveStage', $opportunity, data: [
                'new_stage' => 'proposal',
                'notes' => 'All stakeholders engaged. Moving to proposal phase.',
            ]);

        // Verify all interactions are logged
        $this->assertDatabaseCount('interactions', 3);
        $this->assertDatabaseHas('interactions', ['contact_id' => $primaryContact->id]);
        $this->assertDatabaseHas('interactions', ['contact_id' => $secondaryContact->id]);
        $this->assertDatabaseHas('interactions', ['contact_id' => $operationsContact->id]);

        $opportunity->refresh();
        $this->assertEquals('proposal', $opportunity->stage);
    }

    public function test_opportunity_pipeline_progression_tracking()
    {
        // Create basic setup
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();
        $opportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($this->user)
            ->create([
                'stage' => 'lead',
                'stage_changed_at' => now(),
                'stage_changed_by_user_id' => $this->user->id,
            ]);

        $stages = ['prospect', 'proposal', 'negotiation', 'closed'];
        
        foreach ($stages as $stage) {
            // Move to next stage
            Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
                ->callTableAction('moveStage', $opportunity, data: [
                    'new_stage' => $stage,
                    'notes' => "Moving to {$stage} stage",
                ]);

            $opportunity->refresh();
            $this->assertEquals($stage, $opportunity->stage);
            $this->assertEquals($this->user->id, $opportunity->stage_changed_by_user_id);
        }

        // Verify final state
        $this->assertEquals('closed', $opportunity->stage);
    }

    public function test_bulk_operations_workflow()
    {
        // Create multiple opportunities for bulk operations
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();
        
        $opportunities = Opportunity::factory()->count(5)
            ->for($organization)
            ->for($contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        // Bulk update priority
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->callTableBulkAction('updatePriority', $opportunities, data: [
                'priority' => 'high',
            ]);

        // Verify all opportunities updated
        foreach ($opportunities as $opportunity) {
            $this->assertDatabaseHas('opportunities', [
                'id' => $opportunity->id,
                'priority' => 'high',
            ]);
        }

        // Create multiple contacts for bulk organization change
        $newOrganization = Organization::factory()->create();
        $contacts = Contact::factory()->count(3)->for($organization)->create();

        // Bulk change organization
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableBulkAction('change_organization', $contacts, data: [
                'organization_id' => $newOrganization->id,
            ]);

        // Verify all contacts moved
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contacts', [
                'id' => $contact->id,
                'organization_id' => $newOrganization->id,
            ]);
        }
    }

    public function test_search_and_filter_workflow()
    {
        // Create test data
        $restaurant1 = Organization::factory()->create(['name' => 'Italian Bistro', 'type' => 'CLIENT']);
        $restaurant2 = Organization::factory()->create(['name' => 'Sushi Palace', 'type' => 'PROSPECT']);
        $restaurant3 = Organization::factory()->create(['name' => 'Burger Joint', 'type' => 'CLIENT']);

        $contact1 = Contact::factory()->for($restaurant1)->create(['firstName' => 'Marco', 'isPrimary' => true]);
        $contact2 = Contact::factory()->for($restaurant2)->create(['firstName' => 'Yuki', 'isPrimary' => false]);

        $opportunity1 = Opportunity::factory()->for($restaurant1)->for($contact1)->for($this->user)->create(['stage' => 'closed', 'status' => 'won']);
        $opportunity2 = Opportunity::factory()->for($restaurant2)->for($contact2)->for($this->user)->create(['stage' => 'proposal', 'status' => 'open']);

        // Test organization search
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->searchTable('Italian')
            ->assertCanSeeTableRecords([$restaurant1])
            ->assertCanNotSeeTableRecords([$restaurant2, $restaurant3]);

        // Test organization filter by type
        Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->filterTable('type', 'CLIENT')
            ->assertCanSeeTableRecords([$restaurant1, $restaurant3])
            ->assertCanNotSeeTableRecords([$restaurant2]);

        // Test contact filter by primary status
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('primary_contacts')
            ->assertCanSeeTableRecords([$contact1])
            ->assertCanNotSeeTableRecords([$contact2]);

        // Test opportunity filter by stage
        Livewire::test(OpportunityResource\Pages\ListOpportunities::class)
            ->filterTable('stage', 'closed')
            ->assertCanSeeTableRecords([$opportunity1])
            ->assertCanNotSeeTableRecords([$opportunity2]);
    }
}