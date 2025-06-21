<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->for($organization)->create();

        $this->assertInstanceOf(Organization::class, $contact->organization);
        $this->assertEquals($organization->id, $contact->organization->id);
    }

    public function test_contact_has_many_interactions()
    {
        $contact = Contact::factory()->create();
        $interactions = Interaction::factory()->count(3)->for($contact)->create();

        $this->assertCount(3, $contact->interactions);
        $this->assertTrue($contact->interactions->first() instanceof Interaction);
    }

    public function test_contact_has_many_opportunities()
    {
        $contact = Contact::factory()->create();
        $opportunities = Opportunity::factory()->count(2)->for($contact)->create();

        $this->assertCount(2, $contact->opportunities);
        $this->assertTrue($contact->opportunities->first() instanceof Opportunity);
    }

    public function test_full_name_accessor()
    {
        $contact = Contact::factory()->create([
            'firstName' => 'John',
            'lastName' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $contact->full_name);
    }

    public function test_display_name_accessor()
    {
        $contact = Contact::factory()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'position' => 'Manager'
        ]);

        $this->assertEquals('John Doe (Manager)', $contact->display_name);

        $contactWithoutPosition = Contact::factory()->create([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'position' => null
        ]);

        $this->assertEquals('Jane Smith', $contactWithoutPosition->display_name);
    }

    public function test_primary_status_accessor()
    {
        $primaryContact = Contact::factory()->create(['isPrimary' => true]);
        $this->assertEquals('Primary Contact', $primaryContact->primary_status);

        $secondaryContact = Contact::factory()->create(['isPrimary' => false]);
        $this->assertEquals('Secondary Contact', $secondaryContact->primary_status);
    }

    public function test_contact_info_accessor()
    {
        $contact = Contact::factory()->create([
            'email' => 'john@example.com',
            'phone' => '555-1234'
        ]);

        $this->assertEquals('john@example.com | 555-1234', $contact->contact_info);

        $contactWithoutInfo = Contact::factory()->create([
            'email' => null,
            'phone' => null
        ]);

        $this->assertEquals('No contact info', $contactWithoutInfo->contact_info);
    }

    public function test_primary_scope()
    {
        $primaryContact = Contact::factory()->create(['isPrimary' => true]);
        $secondaryContact = Contact::factory()->create(['isPrimary' => false]);

        $results = Contact::primary()->get();

        $this->assertTrue($results->contains($primaryContact));
        $this->assertFalse($results->contains($secondaryContact));
    }

    public function test_for_organization_scope()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        
        $contact1 = Contact::factory()->for($organization1)->create();
        $contact2 = Contact::factory()->for($organization2)->create();

        $results = Contact::forOrganization($organization1->id)->get();

        $this->assertTrue($results->contains($contact1));
        $this->assertFalse($results->contains($contact2));
    }

    public function test_with_email_scope()
    {
        $contactWithEmail = Contact::factory()->create(['email' => 'test@example.com']);
        $contactWithoutEmail = Contact::factory()->create(['email' => null]);

        $results = Contact::withEmail()->get();

        $this->assertTrue($results->contains($contactWithEmail));
        $this->assertFalse($results->contains($contactWithoutEmail));
    }

    public function test_with_phone_scope()
    {
        $contactWithPhone = Contact::factory()->create(['phone' => '555-1234']);
        $contactWithoutPhone = Contact::factory()->create(['phone' => null]);

        $results = Contact::withPhone()->get();

        $this->assertTrue($results->contains($contactWithPhone));
        $this->assertFalse($results->contains($contactWithoutPhone));
    }

    public function test_fillable_attributes()
    {
        $organization = Organization::factory()->create();
        $data = [
            'organization_id' => $organization->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'unauthorized_field' => 'should not be set'
        ];

        $contact = Contact::create($data);
        
        $this->assertEquals('John', $contact->firstName);
        $this->assertEquals('Doe', $contact->lastName);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertNull($contact->unauthorized_field);
    }

    public function test_casts_are_applied()
    {
        $contact = Contact::factory()->create(['isPrimary' => 1]);

        $this->assertIsBool($contact->isPrimary);
        $this->assertTrue($contact->isPrimary);
    }
}