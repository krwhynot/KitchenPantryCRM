<?php

namespace Tests\Feature\Models;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Principal;
use App\Models\ProductLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_crm_workflow_with_relationships()
    {
        // Create a complete CRM scenario with all relationships
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();
        
        // Create interactions and opportunities
        $interactions = Interaction::factory()
            ->count(3)
            ->for($organization)
            ->for($contact)
            ->for($user)
            ->create();
            
        $opportunities = Opportunity::factory()
            ->count(2)
            ->for($organization)
            ->for($contact)
            ->for($user)
            ->create();

        // Test all relationships work correctly
        $this->assertCount(3, $organization->interactions);
        $this->assertCount(2, $organization->opportunities);
        $this->assertCount(1, $organization->contacts);
        
        $this->assertCount(3, $contact->interactions);
        $this->assertCount(2, $contact->opportunities);
        $this->assertEquals($organization->id, $contact->organization->id);
        
        $this->assertCount(3, $user->interactions);
        $this->assertCount(2, $user->opportunities);
    }

    public function test_principal_product_line_relationship_workflow()
    {
        // Create principal with multiple product lines
        $principal = Principal::factory()->create([
            'name' => 'Acme Food Distributors',
            'contact_name' => 'John Smith'
        ]);
        
        $productLines = ProductLine::factory()
            ->count(4)
            ->for($principal)
            ->create([
                'is_active' => true
            ]);

        // Test relationship loading
        $this->assertCount(4, $principal->productLines);
        $this->assertTrue($principal->productLines->every(fn($line) => $line->is_active));
        
        // Test reverse relationship
        $firstProductLine = $productLines->first();
        $this->assertEquals($principal->id, $firstProductLine->principal->id);
        $this->assertEquals('Acme Food Distributors', $firstProductLine->principal->name);
    }

    public function test_complex_organization_hierarchy()
    {
        // Create organization with complete hierarchy
        $organization = Organization::factory()->create([
            'priority' => 'A',
            'segment' => 'FINEDINING'
        ]);
        
        // Primary and secondary contacts
        $primaryContact = Contact::factory()
            ->for($organization)
            ->create(['isPrimary' => true]);
            
        $secondaryContacts = Contact::factory()
            ->count(2)
            ->for($organization)
            ->create(['isPrimary' => false]);
            
        // Multiple users interacting
        $users = User::factory()->count(2)->create();
        
        // Create interactions with different users
        foreach ($users as $user) {
            Interaction::factory()
                ->count(2)
                ->for($organization)
                ->for($primaryContact)
                ->for($user)
                ->create();
                
            Opportunity::factory()
                ->for($organization)
                ->for($primaryContact)
                ->for($user)
                ->create();
        }

        // Test complete hierarchy
        $this->assertCount(3, $organization->contacts);
        $this->assertCount(4, $organization->interactions);
        $this->assertCount(2, $organization->opportunities);
        
        // Test primary contact filtering
        $primaryContacts = $organization->contacts()->where('isPrimary', true)->get();
        $this->assertCount(1, $primaryContacts);
        $this->assertEquals($primaryContact->id, $primaryContacts->first()->id);
    }

    public function test_cascading_deletes_work_correctly()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();
        $user = User::factory()->create();
        
        $interaction = Interaction::factory()
            ->for($organization)
            ->for($contact)
            ->for($user)
            ->create();
            
        $opportunity = Opportunity::factory()
            ->for($organization)
            ->for($contact)
            ->for($user)
            ->create();

        // Store IDs before deletion
        $contactId = $contact->id;
        $interactionId = $interaction->id;
        $opportunityId = $opportunity->id;

        // Delete organization should cascade to contacts
        $organization->delete();

        // Verify cascading deletes worked
        $this->assertDatabaseMissing('contacts', ['id' => $contactId]);
        $this->assertDatabaseMissing('interactions', ['id' => $interactionId]);
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunityId]);
    }

    public function test_model_scopes_work_with_relationships()
    {
        $user = User::factory()->create();
        
        // Create organizations with different priorities
        $highPriorityOrg = Organization::factory()->create(['priority' => 'A']);
        $lowPriorityOrg = Organization::factory()->create(['priority' => 'C']);
        
        // Create opportunities with different stages
        $activeOpportunity = Opportunity::factory()
            ->for($highPriorityOrg)
            ->for($user)
            ->create([
                'stage' => 'proposal',
                'status' => 'open',
                'isActive' => true
            ]);
            
        $closedOpportunity = Opportunity::factory()
            ->for($lowPriorityOrg)
            ->for($user)
            ->create([
                'stage' => 'closed',
                'status' => 'won',
                'isActive' => false
            ]);

        // Test scoped queries
        $highPriorityOrganizations = Organization::byPriority('A')->get();
        $this->assertCount(1, $highPriorityOrganizations);
        $this->assertTrue($highPriorityOrganizations->contains($highPriorityOrg));

        $activeOpportunities = Opportunity::activeOpportunities()->get();
        $this->assertCount(1, $activeOpportunities);
        $this->assertTrue($activeOpportunities->contains($activeOpportunity));
        $this->assertFalse($activeOpportunities->contains($closedOpportunity));
    }

    public function test_accessor_and_mutator_integration()
    {
        $organization = Organization::factory()->create([
            'priority' => 'A',
            'estimatedRevenue' => 50000.50,
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001'
        ]);

        $contact = Contact::factory()->for($organization)->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'position' => 'Manager',
            'email' => 'john@example.com',
            'phone' => '555-1234'
        ]);

        $opportunity = Opportunity::factory()->for($organization)->create([
            'value' => 25000.75,
            'stage' => 'proposal'
        ]);

        // Test accessors work correctly
        $this->assertEquals('High Priority', $organization->priority_label);
        $this->assertEquals('$50,000.50', $organization->estimated_revenue_formatted);
        $this->assertEquals('123 Main St, New York, NY, 10001', $organization->full_address);
        
        $this->assertEquals('John Doe', $contact->full_name);
        $this->assertEquals('John Doe (Manager)', $contact->display_name);
        $this->assertEquals('john@example.com | 555-1234', $contact->contact_info);
        
        $this->assertEquals('Proposal', $opportunity->stage_label);
        $this->assertEquals('$25,000.75', $opportunity->value_formatted);
    }
}