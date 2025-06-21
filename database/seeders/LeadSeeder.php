<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();
        
        // We need a user to assign the lead to. Let's create one if none exist.
        $user = User::first();
        if (!$user) {
            // This assumes you have the default User factory set up.
            $user = User::factory()->create([
                'name' => 'Default Admin',
                'email' => 'admin@pantrycrm.com',
            ]);
        }

        if ($organizations->isEmpty()) {
            $this->command->info('No organizations found, skipping lead seeding.');
            return;
        }

        Lead::create([
            'firstName' => 'Bob',
            'lastName' => 'Builder',
            'email' => 'bob.builder@construction.com',
            'company' => 'Bob\'s Construction',
            'source' => 'Website Inquiry',
            'status' => 'NEW',
            'organization_id' => $organizations->random()->id,
            'assigned_to_id' => $user->id,
        ]);

        Lead::create([
            'firstName' => 'Wendy',
            'lastName' => 'Worker',
            'email' => 'wendy.worker@construction.com',
            'company' => 'Bob\'s Construction',
            'source' => 'Referral',
            'status' => 'CONTACTED',
            'organization_id' => $organizations->random()->id,
            'assigned_to_id' => $user->id,
        ]);

        Lead::create([
            'firstName' => 'Leo',
            'lastName' => 'Leader',
            'email' => 'leo.leader@management.com',
            'company' => 'Leadership Co.',
            'source' => 'Cold Call',
            'status' => 'QUALIFIED',
            'organization_id' => $organizations->random()->id,
            'assigned_to_id' => $user->id,
        ]);
    }
}