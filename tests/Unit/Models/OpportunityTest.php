<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $opportunity = Opportunity::factory()->for($organization)->create();

        $this->assertInstanceOf(Organization::class, $opportunity->organization);
        $this->assertEquals($organization->id, $opportunity->organization->id);
    }

    public function test_opportunity_belongs_to_contact()
    {
        $contact = Contact::factory()->create();
        $opportunity = Opportunity::factory()->for($contact)->create();

        $this->assertInstanceOf(Contact::class, $opportunity->contact);
        $this->assertEquals($contact->id, $opportunity->contact->id);
    }

    public function test_opportunity_belongs_to_user()
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $opportunity->user);
        $this->assertEquals($user->id, $opportunity->user->id);
    }

    public function test_stage_label_accessor()
    {
        $opportunity = Opportunity::factory()->create(['stage' => 'proposal']);
        $this->assertEquals('Proposal', $opportunity->stage_label);

        $opportunity = Opportunity::factory()->create(['stage' => 'in_negotiation']);
        $this->assertEquals('In Negotiation', $opportunity->stage_label);
    }

    public function test_status_label_accessor()
    {
        $opportunity = Opportunity::factory()->create(['status' => 'open']);
        $this->assertEquals('Open', $opportunity->status_label);

        $opportunity = Opportunity::factory()->create(['status' => 'in_progress']);
        $this->assertEquals('In Progress', $opportunity->status_label);
    }

    public function test_value_formatted_accessor()
    {
        $opportunity = Opportunity::factory()->create(['value' => 15000.50]);
        $this->assertEquals('$15,000.50', $opportunity->value_formatted);

        $opportunity = Opportunity::factory()->create(['value' => null]);
        $this->assertEquals('$0.00', $opportunity->value_formatted);
    }

    public function test_expected_close_date_formatted_accessor()
    {
        $opportunity = Opportunity::factory()->create([
            'expectedCloseDate' => '2024-03-15 10:00:00'
        ]);
        $this->assertEquals('Mar 15, 2024', $opportunity->expected_close_date_formatted);

        $opportunity = Opportunity::factory()->create(['expectedCloseDate' => null]);
        $this->assertEquals('Not set', $opportunity->expected_close_date_formatted);
    }

    public function test_active_scope()
    {
        $activeOpportunity = Opportunity::factory()->create(['isActive' => true]);
        $inactiveOpportunity = Opportunity::factory()->create(['isActive' => false]);

        $results = Opportunity::active()->get();

        $this->assertTrue($results->contains($activeOpportunity));
        $this->assertFalse($results->contains($inactiveOpportunity));
    }

    public function test_by_stage_scope()
    {
        $proposalStage = Opportunity::factory()->create(['stage' => 'proposal']);
        $leadStage = Opportunity::factory()->create(['stage' => 'lead']);

        $results = Opportunity::byStage('proposal')->get();

        $this->assertTrue($results->contains($proposalStage));
        $this->assertFalse($results->contains($leadStage));
    }

    public function test_by_status_scope()
    {
        $openOpportunity = Opportunity::factory()->create(['status' => 'open']);
        $wonOpportunity = Opportunity::factory()->create(['status' => 'won']);

        $results = Opportunity::byStatus('open')->get();

        $this->assertTrue($results->contains($openOpportunity));
        $this->assertFalse($results->contains($wonOpportunity));
    }

    public function test_assigned_to_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $opportunity1 = Opportunity::factory()->for($user1)->create();
        $opportunity2 = Opportunity::factory()->for($user2)->create();

        $results = Opportunity::assignedTo($user1->id)->get();

        $this->assertTrue($results->contains($opportunity1));
        $this->assertFalse($results->contains($opportunity2));
    }

    public function test_active_opportunities_scope()
    {
        $activeOpen = Opportunity::factory()->create([
            'isActive' => true,
            'status' => 'open'
        ]);
        $activeInProgress = Opportunity::factory()->create([
            'isActive' => true,
            'status' => 'in_progress'
        ]);
        $activeWon = Opportunity::factory()->create([
            'isActive' => true,
            'status' => 'won'
        ]);
        $inactive = Opportunity::factory()->create(['isActive' => false]);

        $results = Opportunity::activeOpportunities()->get();

        $this->assertTrue($results->contains($activeOpen));
        $this->assertTrue($results->contains($activeInProgress));
        $this->assertFalse($results->contains($activeWon));
        $this->assertFalse($results->contains($inactive));
    }

    public function test_fillable_attributes()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        
        $data = [
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'title' => 'Test Opportunity',
            'value' => 25000.00,
            'unauthorized_field' => 'should not be set'
        ];

        $opportunity = Opportunity::create($data);
        
        $this->assertEquals('Test Opportunity', $opportunity->title);
        $this->assertEquals(25000.00, $opportunity->value);
        $this->assertNull($opportunity->unauthorized_field);
    }

    public function test_casts_are_applied()
    {
        $opportunity = Opportunity::factory()->create([
            'expectedCloseDate' => '2024-03-15 10:00:00',
            'isActive' => 1,
            'value' => '15000.50'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $opportunity->expectedCloseDate);
        $this->assertIsBool($opportunity->isActive);
        $this->assertIsFloat($opportunity->value);
    }
}