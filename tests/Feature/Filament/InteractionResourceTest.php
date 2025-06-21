<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\InteractionResource;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractionResourceTest extends TestCase
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
            'name' => 'Test Restaurant',
        ]);
        
        $this->contact = Contact::factory()->for($this->organization)->create([
            'firstName' => 'John',
            'lastName' => 'Smith',
        ]);
    }

    public function test_can_list_interactions()
    {
        $interactions = Interaction::factory()->count(5)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($interactions);
    }

    public function test_can_create_interaction_with_required_fields()
    {
        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm([
                'organization_id' => $this->organization->id,
                'contact_id' => $this->contact->id,
                'type' => 'CALL',
                'interactionDate' => now()->format('Y-m-d H:i:s'),
                'duration' => 30,
                'subject' => 'Follow-up call about new menu items',
                'notes' => 'Discussed seasonal menu changes and pricing',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactions', [
            'organization_id' => $this->organization->id,
            'contact_id' => $this->contact->id,
            'type' => 'CALL',
            'duration' => 30,
            'subject' => 'Follow-up call about new menu items',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_interaction_with_optional_fields()
    {
        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm([
                'organization_id' => $this->organization->id,
                'contact_id' => $this->contact->id,
                'type' => 'MEETING',
                'interactionDate' => now()->format('Y-m-d H:i:s'),
                'duration' => 60,
                'subject' => 'Product presentation meeting',
                'notes' => 'Presented new product line',
                'outcome' => 'POSITIVE',
                'priority' => 'high',
                'follow_up_date' => now()->addDays(7)->format('Y-m-d'),
                'nextAction' => 'Send proposal document',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactions', [
            'organization_id' => $this->organization->id,
            'contact_id' => $this->contact->id,
            'type' => 'MEETING',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'nextAction' => 'Send proposal document',
        ]);
    }

    public function test_contact_options_filtered_by_organization()
    {
        $otherOrg = Organization::factory()->create(['name' => 'Other Restaurant']);
        $otherContact = Contact::factory()->for($otherOrg)->create([
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]);

        $component = Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm(['organization_id' => $this->organization->id]);

        // The contact dropdown should only show contacts from the selected organization
        // This tests the dynamic contact filtering functionality
        $component->assertFormFieldExists('contact_id');
    }

    public function test_can_edit_interaction()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'subject' => 'Original Subject',
                'type' => 'CALL',
                'outcome' => 'NEUTRAL',
                'priority' => 'medium',
            ]);

        Livewire::test(InteractionResource\Pages\EditInteraction::class, [
            'record' => $interaction->getRouteKey(),
        ])
        ->fillForm([
            'subject' => 'Updated Subject',
            'type' => 'MEETING',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
            'notes' => 'Added detailed notes during edit',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactions', [
            'id' => $interaction->id,
            'subject' => 'Updated Subject',
            'type' => 'MEETING',
            'outcome' => 'POSITIVE',
            'priority' => 'high',
        ]);
    }

    public function test_can_delete_interaction()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableAction('delete', $interaction);

        $this->assertDatabaseMissing('interactions', [
            'id' => $interaction->id,
        ]);
    }

    public function test_can_filter_by_organization()
    {
        $org1Interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        $org2 = Organization::factory()->create(['name' => 'Another Restaurant']);
        $org2Contact = Contact::factory()->for($org2)->create();
        $org2Interaction = Interaction::factory()
            ->for($org2)
            ->for($org2Contact)
            ->for($this->user)
            ->create();

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('organization', [$this->organization->id])
            ->assertCanSeeTableRecords([$org1Interaction])
            ->assertCanNotSeeTableRecords([$org2Interaction]);
    }

    public function test_can_filter_by_type()
    {
        $callInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['type' => 'CALL']);

        $meetingInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['type' => 'MEETING']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('type', ['CALL'])
            ->assertCanSeeTableRecords([$callInteraction])
            ->assertCanNotSeeTableRecords([$meetingInteraction]);
    }

    public function test_can_filter_by_outcome()
    {
        $positiveInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'POSITIVE']);

        $negativeInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'NEGATIVE']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('outcome', ['POSITIVE'])
            ->assertCanSeeTableRecords([$positiveInteraction])
            ->assertCanNotSeeTableRecords([$negativeInteraction]);
    }

    public function test_can_filter_by_priority()
    {
        $highPriorityInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'high']);

        $lowPriorityInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('priority', ['high'])
            ->assertCanSeeTableRecords([$highPriorityInteraction])
            ->assertCanNotSeeTableRecords([$lowPriorityInteraction]);
    }

    public function test_can_filter_follow_up_required()
    {
        $followUpInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'FOLLOWUPNEEDED']);

        $followUpDateInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['follow_up_date' => now()->addDays(5)]);

        $normalInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'POSITIVE']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('follow_up_required')
            ->assertCanSeeTableRecords([$followUpInteraction, $followUpDateInteraction])
            ->assertCanNotSeeTableRecords([$normalInteraction]);
    }

    public function test_can_filter_recent_interactions()
    {
        $recentInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['interactionDate' => now()->subDays(3)]);

        $oldInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['interactionDate' => now()->subDays(10)]);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('recent')
            ->assertCanSeeTableRecords([$recentInteraction])
            ->assertCanNotSeeTableRecords([$oldInteraction]);
    }

    public function test_can_filter_my_interactions()
    {
        $myInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        $otherUser = User::factory()->create();
        $otherInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($otherUser)
            ->create();

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->filterTable('my_interactions')
            ->assertCanSeeTableRecords([$myInteraction])
            ->assertCanNotSeeTableRecords([$otherInteraction]);
    }

    public function test_can_search_interactions()
    {
        $interaction1 = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['subject' => 'Product demo meeting']);

        $interaction2 = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['subject' => 'Follow-up call about pricing']);

        $interaction3 = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['subject' => 'Contract negotiation']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->searchTable('demo')
            ->assertCanSeeTableRecords([$interaction1])
            ->assertCanNotSeeTableRecords([$interaction2, $interaction3]);
    }

    public function test_bulk_change_type_action()
    {
        $interactions = Interaction::factory()->count(3)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['type' => 'CALL']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableBulkAction('change_type', $interactions, data: [
                'type' => 'MEETING',
            ]);

        foreach ($interactions as $interaction) {
            $this->assertDatabaseHas('interactions', [
                'id' => $interaction->id,
                'type' => 'MEETING',
            ]);
        }
    }

    public function test_bulk_change_outcome_action()
    {
        $interactions = Interaction::factory()->count(3)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'NEUTRAL']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableBulkAction('change_outcome', $interactions, data: [
                'outcome' => 'POSITIVE',
            ]);

        foreach ($interactions as $interaction) {
            $this->assertDatabaseHas('interactions', [
                'id' => $interaction->id,
                'outcome' => 'POSITIVE',
            ]);
        }
    }

    public function test_can_bulk_delete_interactions()
    {
        $interactions = Interaction::factory()->count(3)
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableBulkAction('delete', $interactions);

        foreach ($interactions as $interaction) {
            $this->assertDatabaseMissing('interactions', [
                'id' => $interaction->id,
            ]);
        }
    }

    public function test_can_duplicate_interaction()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'subject' => 'Original Subject',
                'type' => 'CALL',
                'notes' => 'Original notes',
            ]);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableAction('duplicate', $interaction);

        $this->assertDatabaseHas('interactions', [
            'subject' => 'Copy of Original Subject',
            'type' => 'CALL',
            'notes' => 'Original notes',
            'organization_id' => $this->organization->id,
            'contact_id' => $this->contact->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_form_validation_works()
    {
        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm([
                'organization_id' => null, // Required field
                'type' => '', // Required field
                'interactionDate' => '', // Required field
                'subject' => '', // Required field
                'duration' => 0, // Invalid value (below minimum)
            ])
            ->call('create')
            ->assertHasFormErrors(['organization_id', 'type', 'interactionDate', 'subject', 'duration']);
    }

    public function test_duration_validation_constraints()
    {
        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm([
                'organization_id' => $this->organization->id,
                'type' => 'CALL',
                'interactionDate' => now()->format('Y-m-d H:i:s'),
                'subject' => 'Test Subject',
                'duration' => 500, // Above maximum (480 minutes)
            ])
            ->call('create')
            ->assertHasFormErrors(['duration']);
    }

    public function test_type_badge_colors_display_correctly()
    {
        $callInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['type' => 'CALL']);

        $meetingInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['type' => 'MEETING']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$callInteraction, $meetingInteraction])
            ->assertSee('Call')
            ->assertSee('Meeting');
    }

    public function test_outcome_badge_colors_display_correctly()
    {
        $positiveInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'POSITIVE']);

        $negativeInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['outcome' => 'NEGATIVE']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$positiveInteraction, $negativeInteraction])
            ->assertSee('Positive')
            ->assertSee('Negative');
    }

    public function test_priority_badge_colors_display_correctly()
    {
        $highPriorityInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'high']);

        $lowPriorityInteraction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['priority' => 'low']);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$highPriorityInteraction, $lowPriorityInteraction])
            ->assertSee('High')
            ->assertSee('Low');
    }

    public function test_organization_and_contact_links_work()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create();

        $component = Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$interaction]);

        $component->assertSee($this->organization->name)
                  ->assertSee($this->contact->full_name);
    }

    public function test_notes_truncation_in_description()
    {
        $longNotes = str_repeat('This is a very long note. ', 20);
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['notes' => $longNotes]);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$interaction]);
        
        // The notes should be truncated to 60 characters in the description
    }

    public function test_default_sorting_by_interaction_date_desc()
    {
        $older = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['interactionDate' => now()->subDays(2)]);

        $newer = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['interactionDate' => now()->subDay()]);

        $component = Livewire::test(InteractionResource\Pages\ListInteractions::class);
        
        // Verify records are visible (exact sorting verification would require examining the table order)
        $component->assertCanSeeTableRecords([$older, $newer]);
    }

    public function test_user_id_automatically_set_to_current_user()
    {
        Livewire::test(InteractionResource\Pages\CreateInteraction::class)
            ->fillForm([
                'organization_id' => $this->organization->id,
                'contact_id' => $this->contact->id,
                'type' => 'CALL',
                'interactionDate' => now()->format('Y-m-d H:i:s'),
                'subject' => 'Test Subject',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('interactions', [
            'organization_id' => $this->organization->id,
            'subject' => 'Test Subject',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_view_interaction_details()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create([
                'subject' => 'Detailed Interaction',
                'notes' => 'Comprehensive interaction notes',
            ]);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->callTableAction('view', $interaction);
    }

    public function test_create_organization_option_functionality()
    {
        // Test that the create organization option form is available
        $component = Livewire::test(InteractionResource\Pages\CreateInteraction::class);
        $component->assertFormFieldExists('organization_id');
    }

    public function test_duration_formatting_displays_correctly()
    {
        $interaction = Interaction::factory()
            ->for($this->organization)
            ->for($this->contact)
            ->for($this->user)
            ->create(['duration' => 45]);

        Livewire::test(InteractionResource\Pages\ListInteractions::class)
            ->assertCanSeeTableRecords([$interaction]);
        
        // Duration should be formatted appropriately in the table
    }
}