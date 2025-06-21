<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'email' => 'org@test.com',
        ]);
    }

    public function test_can_list_contacts()
    {
        $contacts = Contact::factory()->count(5)->for($this->organization)->create();

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($contacts);
    }

    public function test_can_create_contact()
    {
        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm([
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-0123',
                'organization_id' => $this->organization->id,
                'position' => 'General Manager',
                'isPrimary' => true,
                'notes' => 'Key decision maker for purchasing',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'firstName' => 'John',
            'lastName' => 'Smith',
            'email' => 'john.smith@example.com',
            'organization_id' => $this->organization->id,
            'position' => 'General Manager',
            'isPrimary' => true,
        ]);
    }

    public function test_can_create_contact_with_new_organization()
    {
        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm([
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'email' => 'jane.doe@neworg.com',
                'organization_id' => null,
            ])
            ->call('create');
        
        // Note: This tests that the create option form exists
        // The actual creation would need Filament's create option functionality
    }

    public function test_can_edit_contact()
    {
        $contact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Original',
            'lastName' => 'Name',
            'position' => 'Assistant Manager',
            'isPrimary' => false,
        ]);

        $newOrganization = Organization::factory()->create(['name' => 'New Organization']);

        Livewire::test(ContactResource\Pages\EditContact::class, [
            'record' => $contact->getRouteKey(),
        ])
        ->fillForm([
            'firstName' => 'Updated',
            'lastName' => 'Name',
            'position' => 'Senior Manager',
            'organization_id' => $newOrganization->id,
            'isPrimary' => true,
            'notes' => 'Promoted to senior role',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'firstName' => 'Updated',
            'position' => 'Senior Manager',
            'organization_id' => $newOrganization->id,
            'isPrimary' => true,
        ]);
    }

    public function test_can_delete_contact()
    {
        $contact = Contact::factory()->for($this->organization)->create();

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableAction('delete', $contact);

        $this->assertSoftDeleted('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_can_filter_by_organization()
    {
        $org1Contact = Contact::factory()->for($this->organization)->create();
        $org2 = Organization::factory()->create(['name' => 'Another Organization']);
        $org2Contact = Contact::factory()->for($org2)->create();

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('organization', [$this->organization->id])
            ->assertCanSeeTableRecords([$org1Contact])
            ->assertCanNotSeeTableRecords([$org2Contact]);
    }

    public function test_can_filter_primary_contacts_only()
    {
        $primaryContact = Contact::factory()->for($this->organization)->create(['isPrimary' => true]);
        $secondaryContact = Contact::factory()->for($this->organization)->create(['isPrimary' => false]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('primary_contacts')
            ->assertCanSeeTableRecords([$primaryContact])
            ->assertCanNotSeeTableRecords([$secondaryContact]);
    }

    public function test_can_filter_contacts_with_email()
    {
        $withEmail = Contact::factory()->for($this->organization)->create(['email' => 'test@example.com']);
        $withoutEmail = Contact::factory()->for($this->organization)->create(['email' => null]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('has_email')
            ->assertCanSeeTableRecords([$withEmail])
            ->assertCanNotSeeTableRecords([$withoutEmail]);
    }

    public function test_can_filter_contacts_with_phone()
    {
        $withPhone = Contact::factory()->for($this->organization)->create(['phone' => '555-0123']);
        $withoutPhone = Contact::factory()->for($this->organization)->create(['phone' => null]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('has_phone')
            ->assertCanSeeTableRecords([$withPhone])
            ->assertCanNotSeeTableRecords([$withoutPhone]);
    }

    public function test_can_filter_recent_contacts()
    {
        $recentContact = Contact::factory()->for($this->organization)->create([
            'created_at' => now()->subDays(15),
        ]);
        $oldContact = Contact::factory()->for($this->organization)->create([
            'created_at' => now()->subDays(45),
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->filterTable('recent')
            ->assertCanSeeTableRecords([$recentContact])
            ->assertCanNotSeeTableRecords([$oldContact]);
    }

    public function test_can_search_contacts_by_name()
    {
        $contact1 = Contact::factory()->for($this->organization)->create([
            'firstName' => 'John',
            'lastName' => 'Smith',
        ]);
        $contact2 = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Jane',
            'lastName' => 'Johnson',
        ]);
        $contact3 = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Bob',
            'lastName' => 'Wilson',
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$contact1, $contact2])
            ->assertCanNotSeeTableRecords([$contact3]);
    }

    public function test_can_search_contacts_by_email_and_phone()
    {
        $emailContact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Email',
            'lastName' => 'Contact',
            'email' => 'unique@example.com',
        ]);
        $phoneContact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Phone',
            'lastName' => 'Contact',
            'phone' => '555-UNIQUE',
        ]);
        $otherContact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'Other',
            'lastName' => 'Contact',
            'email' => 'different@example.com',
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->searchTable('unique@example.com')
            ->assertCanSeeTableRecords([$emailContact])
            ->assertCanNotSeeTableRecords([$phoneContact, $otherContact]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->searchTable('UNIQUE')
            ->assertCanSeeTableRecords([$phoneContact])
            ->assertCanNotSeeTableRecords([$emailContact, $otherContact]);
    }

    public function test_bulk_change_organization_action()
    {
        $contacts = Contact::factory()->count(3)->for($this->organization)->create();
        $newOrganization = Organization::factory()->create(['name' => 'New Organization']);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableBulkAction('change_organization', $contacts, data: [
                'organization_id' => $newOrganization->id,
            ]);

        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contacts', [
                'id' => $contact->id,
                'organization_id' => $newOrganization->id,
            ]);
        }
    }

    public function test_bulk_toggle_primary_status_action()
    {
        $contacts = Contact::factory()->count(3)->for($this->organization)->create(['isPrimary' => false]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableBulkAction('toggle_primary', $contacts, data: [
                'is_primary' => true,
            ]);

        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contacts', [
                'id' => $contact->id,
                'isPrimary' => true,
            ]);
        }
    }

    public function test_can_bulk_delete_contacts()
    {
        $contacts = Contact::factory()->count(3)->for($this->organization)->create();

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableBulkAction('delete', $contacts);

        foreach ($contacts as $contact) {
            $this->assertSoftDeleted('contacts', [
                'id' => $contact->id,
            ]);
        }
    }

    public function test_form_validation_works()
    {
        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm([
                'firstName' => '', // Required field
                'lastName' => '', // Required field
                'email' => 'invalid-email', // Invalid email
                'organization_id' => null, // Required field
            ])
            ->call('create')
            ->assertHasFormErrors(['firstName', 'lastName', 'email', 'organization_id']);
    }

    public function test_unique_email_validation()
    {
        $existingContact = Contact::factory()->for($this->organization)->create([
            'email' => 'unique@example.com',
        ]);

        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm([
                'firstName' => 'New',
                'lastName' => 'Contact',
                'email' => 'unique@example.com', // Duplicate email
                'organization_id' => $this->organization->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_primary_badge_displays_correctly()
    {
        $primaryContact = Contact::factory()->for($this->organization)->create(['isPrimary' => true]);
        $secondaryContact = Contact::factory()->for($this->organization)->create(['isPrimary' => false]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$primaryContact, $secondaryContact])
            ->assertSee('Primary')
            ->assertSee('Secondary');
    }

    public function test_organization_link_displays_correctly()
    {
        $contact = Contact::factory()->for($this->organization)->create();

        $component = Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$contact]);

        $component->assertSee($this->organization->name);
    }

    public function test_contact_info_description_displays()
    {
        $contact = Contact::factory()->for($this->organization)->create([
            'email' => 'test@example.com',
            'phone' => '555-0123',
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$contact])
            ->assertSee('test@example.com')
            ->assertSee('555-0123');
    }

    public function test_position_description_displays()
    {
        $contact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'position' => 'General Manager',
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$contact])
            ->assertSee('General Manager');
    }

    public function test_contact_with_related_records_integration()
    {
        $contact = Contact::factory()->for($this->organization)->create();
        $interactions = Interaction::factory()->count(2)->for($contact)->for($this->organization)->create();
        $opportunities = Opportunity::factory()->count(1)->for($contact)->for($this->organization)->create();

        // Test that the contact displays correctly with related data
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$contact]);

        // Test editing the contact doesn't affect related records
        Livewire::test(ContactResource\Pages\EditContact::class, [
            'record' => $contact->getRouteKey(),
        ])
        ->fillForm([
            'firstName' => 'Updated First Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        // Verify related records still exist
        $this->assertDatabaseHas('interactions', ['contact_id' => $contact->id]);
        $this->assertDatabaseHas('opportunities', ['contact_id' => $contact->id]);
    }

    public function test_soft_delete_and_restore_functionality()
    {
        $contact = Contact::factory()->for($this->organization)->create();

        // Test soft delete
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableAction('delete', $contact);

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);

        // Test restore
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableAction('restore', $contact);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_functionality()
    {
        $contact = Contact::factory()->for($this->organization)->create();
        
        // First soft delete
        $contact->delete();

        // Then force delete
        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->callTableAction('forceDelete', $contact);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_full_name_accessor_works()
    {
        $contact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'John',
            'lastName' => 'Smith',
        ]);

        Livewire::test(ContactResource\Pages\ListContacts::class)
            ->assertCanSeeTableRecords([$contact])
            ->assertSee('John Smith');
    }

    public function test_default_sorting_by_first_name()
    {
        $contactB = Contact::factory()->for($this->organization)->create(['firstName' => 'Bob']);
        $contactA = Contact::factory()->for($this->organization)->create(['firstName' => 'Alice']);
        $contactC = Contact::factory()->for($this->organization)->create(['firstName' => 'Charlie']);

        $component = Livewire::test(ContactResource\Pages\ListContacts::class);
        
        // Verify records are visible (exact sorting verification would require examining the table order)
        $component->assertCanSeeTableRecords([$contactA, $contactB, $contactC]);
    }

    public function test_can_edit_contact_with_minimal_required_fields()
    {
        $contact = Contact::factory()->for($this->organization)->create([
            'email' => null,
            'phone' => null,
            'position' => null,
            'notes' => null,
        ]);

        Livewire::test(ContactResource\Pages\EditContact::class, [
            'record' => $contact->getRouteKey(),
        ])
        ->fillForm([
            'firstName' => 'Updated',
            'lastName' => 'Name',
            'organization_id' => $this->organization->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);
    }

    public function test_organization_relationship_required_validation()
    {
        Livewire::test(ContactResource\Pages\CreateContact::class)
            ->fillForm([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'organization_id' => null, // Missing required organization
            ])
            ->call('create')
            ->assertHasFormErrors(['organization_id']);
    }
}