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

        if ($contacts->isEmpty()) {
            $this->command->info('No contacts found, skipping interaction seeding.');
            return;
        }

        foreach ($contacts as $contact) {
            Interaction::create([
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'type' => 'EMAIL',
                'subject' => 'Follow-up on our recent discussion',
                'date' => now()->subDays(rand(1, 30)),
                'outcome' => 'POSITIVE',
            ]);

            Interaction::create([
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'type' => 'CALL',
                'subject' => 'Quick check-in call',
                'date' => now()->subDays(rand(1, 30)),
                'duration' => 15,
                'outcome' => 'NEUTRAL',
                'nextAction' => 'Send proposal by EOD Friday.'
            ]);
        }
    }
}
