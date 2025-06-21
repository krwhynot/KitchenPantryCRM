<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Opportunity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get opportunities that are in a "won" or "proposal" stage to create contracts for.
        $opportunities = Opportunity::whereIn('stage', ['PROPOSAL', 'NEGOTIATION'])->with(['organization', 'contact'])->get();

        if ($opportunities->isEmpty()) {
            $this->command->info('No suitable opportunities found, skipping contract seeding.');
            return;
        }

        foreach ($opportunities as $opportunity) {
            Contract::create([
                'name' => 'Service Contract for ' . $opportunity->name,
                'organization_id' => $opportunity->organization_id,
                'opportunity_id' => $opportunity->id,
                'contact_id' => $opportunity->contact_id,
                'details' => 'Standard service level agreement for the provision of goods and services.',
                'start_date' => now()->addDays(rand(1, 15)),
                'end_date' => now()->addDays(rand(1, 15))->addYear(),
                'status' => 'ACTIVE',
            ]);
        }
    }
}
