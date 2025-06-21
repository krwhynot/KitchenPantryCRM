<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_has_many_contacts()
    {
        $organization = Organization::factory()->create();
        $contacts = Contact::factory()->count(3)->for($organization)->create();

        $this->assertCount(3, $organization->contacts);
        $this->assertTrue($organization->contacts->first() instanceof Contact);
    }

    public function test_organization_has_many_interactions()
    {
        $organization = Organization::factory()->create();
        $interactions = Interaction::factory()->count(2)->for($organization)->create();

        $this->assertCount(2, $organization->interactions);
        $this->assertTrue($organization->interactions->first() instanceof Interaction);
    }

    public function test_organization_has_many_opportunities()
    {
        $organization = Organization::factory()->create();
        $opportunities = Opportunity::factory()->count(2)->for($organization)->create();

        $this->assertCount(2, $organization->opportunities);
        $this->assertTrue($organization->opportunities->first() instanceof Opportunity);
    }

    public function test_priority_label_accessor()
    {
        $organization = Organization::factory()->create(['priority' => 'A']);
        $this->assertEquals('High Priority', $organization->priority_label);

        $organization = Organization::factory()->create(['priority' => 'B']);
        $this->assertEquals('Medium Priority', $organization->priority_label);

        $organization = Organization::factory()->create(['priority' => 'C']);
        $this->assertEquals('Low Priority', $organization->priority_label);

        $organization = Organization::factory()->create(['priority' => 'D']);
        $this->assertEquals('Lowest Priority', $organization->priority_label);
    }

    public function test_type_label_accessor()
    {
        $organization = Organization::factory()->create(['type' => 'FAST_FOOD']);
        $this->assertEquals('Fast Food', $organization->type_label);

        $organization = Organization::factory()->create(['type' => 'FINE_DINING']);
        $this->assertEquals('Fine Dining', $organization->type_label);
    }

    public function test_full_address_accessor()
    {
        $organization = Organization::factory()->create([
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001'
        ]);

        $this->assertEquals('123 Main St, New York, NY, 10001', $organization->full_address);
    }

    public function test_estimated_revenue_formatted_accessor()
    {
        $organization = Organization::factory()->create(['estimatedRevenue' => 50000.50]);
        $this->assertEquals('$50,000.50', $organization->estimated_revenue_formatted);

        $organization = Organization::factory()->create(['estimatedRevenue' => null]);
        $this->assertEquals('Not specified', $organization->estimated_revenue_formatted);
    }

    public function test_by_priority_scope()
    {
        $highPriority = Organization::factory()->create(['priority' => 'A']);
        $lowPriority = Organization::factory()->create(['priority' => 'C']);

        $results = Organization::byPriority('A')->get();

        $this->assertTrue($results->contains($highPriority));
        $this->assertFalse($results->contains($lowPriority));
    }

    public function test_active_scope()
    {
        $active = Organization::factory()->create(['status' => 'ACTIVE']);
        $inactive = Organization::factory()->create(['status' => 'INACTIVE']);

        $results = Organization::active()->get();

        $this->assertTrue($results->contains($active));
        $this->assertFalse($results->contains($inactive));
    }

    public function test_by_segment_scope()
    {
        $fastFood = Organization::factory()->create(['segment' => 'FASTFOOD']);
        $fineDining = Organization::factory()->create(['segment' => 'FINEDINING']);

        $results = Organization::bySegment('FASTFOOD')->get();

        $this->assertTrue($results->contains($fastFood));
        $this->assertFalse($results->contains($fineDining));
    }

    public function test_fillable_attributes()
    {
        $data = [
            'name' => 'Test Organization',
            'priority' => 'A',
            'segment' => 'FASTFOOD',
            'unauthorized_field' => 'should not be set'
        ];

        $organization = Organization::create($data);
        
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals('A', $organization->priority);
        $this->assertEquals('FASTFOOD', $organization->segment);
        $this->assertNull($organization->unauthorized_field);
    }

    public function test_casts_are_applied()
    {
        $organization = Organization::factory()->create([
            'lastContactDate' => '2024-01-15 10:00:00',
            'estimatedRevenue' => '50000.50',
            'employeeCount' => '25'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $organization->lastContactDate);
        $this->assertIsFloat($organization->estimatedRevenue);
        $this->assertIsInt($organization->employeeCount);
    }
}