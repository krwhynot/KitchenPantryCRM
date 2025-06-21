<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Interaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = Contact::with('organization')->get();
        $firstUser = \App\Models\User::first();

        if ($contacts->isEmpty()) {
            $this->command->info('No contacts found, skipping interaction seeding.');
            return;
        }

        if (!$firstUser) {
            $this->command->info('No users found, skipping interaction seeding.');
            return;
        }

        foreach ($contacts as $contact) {
            Interaction::create([
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'user_id' => $firstUser->id,
                'type' => 'EMAIL',
                'subject' => 'Follow-up on our recent discussion',
                'notes' => 'Discussed their current challenges and potential solutions.',
                'interactionDate' => now()->subDays(rand(1, 30)),
                'duration' => 30,
                'outcome' => 'POSITIVE',
                'priority' => 'medium',
            ]);

            Interaction::create([
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'user_id' => $firstUser->id,
                'type' => 'CALL',
                'subject' => 'Quick check-in call',
                'notes' => 'Brief check-in to maintain relationship.',
                'interactionDate' => now()->subDays(rand(1, 30)),
                'duration' => 15,
                'outcome' => 'NEUTRAL',
                'priority' => 'low',
                'nextAction' => 'Send proposal by EOD Friday.',
                'follow_up_date' => now()->addDays(7),
            ]);
        }
    }
}
