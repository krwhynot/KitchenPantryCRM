<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = Contact::with('organization')->get();

        if ($contacts->isEmpty()) {
            $this->command->info('No contacts found, skipping opportunity seeding.');
            return;
        }

        foreach ($contacts as $contact) {
            Opportunity::create([
                'name' => 'New Equipment Deal for ' . $contact->organization->name,
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'value' => rand(5000, 25000),
                'stage' => 'QUALIFICATION',
                'probability' => 25,
                'expectedCloseDate' => now()->addMonths(rand(1, 3)),
            ]);

            Opportunity::create([
                'name' => 'Annual Supply Contract with ' . $contact->organization->name,
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->id,
                'value' => rand(50000, 150000),
                'stage' => 'PROPOSAL',
                'probability' => 60,
                'expectedCloseDate' => now()->addMonths(rand(2, 6)),
            ]);
        }
    }
}
