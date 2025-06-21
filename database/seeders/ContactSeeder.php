<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $this->command->info('No organizations found, skipping contact seeding.');
            return;
        }

        Contact::create([
            'organization_id' => $organizations->random()->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'position' => 'CEO',
            'email' => 'john.doe@globalfoods.com',
            'phone' => '111-222-3333',
            'isPrimary' => true
        ]);

        Contact::create([
            'organization_id' => $organizations->random()->id,
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'position' => 'Purchasing Manager',
            'email' => 'jane.smith@localcatering.com',
            'phone' => '444-555-6666',
            'isPrimary' => false
        ]);

        Contact::create([
            'organization_id' => $organizations->random()->id,
            'firstName' => 'Peter',
            'lastName' => 'Jones',
            'position' => 'Head Chef',
            'email' => 'peter.jones@farmfresh.com',
            'phone' => '777-888-9999',
            'isPrimary' => false
        ]);
    }
}
