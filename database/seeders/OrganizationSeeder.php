<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::create([
            'name' => 'Global Foods Inc.',
            'priority' => 'A',
            'segment' => 'Distributor',
            'type' => 'Distributor',
            'status' => 'ACTIVE',
            'address' => '123 Global Way',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001',
            'website' => 'https://globalfoods.com',
            'notes' => 'A major international food distributor.'
        ]);

        Organization::create([
            'name' => 'Local Catering Co.',
            'priority' => 'B',
            'segment' => 'Catering',
            'type' => 'Client',
            'status' => 'ACTIVE',
            'address' => '456 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zipCode' => '90210',
            'website' => 'https://localcatering.com',
            'notes' => 'A key local catering partner.'
        ]);

        Organization::create([
            'name' => 'Farm Fresh Produce',
            'priority' => 'C',
            'segment' => 'Supplier',
            'type' => 'Supplier',
            'status' => 'PROSPECT',
            'address' => '789 Farm Rd',
            'city' => 'Greenville',
            'state' => 'TX',
            'zipCode' => '75401',
            'website' => 'https://farmfresh.com',
            'notes' => 'Potential new supplier for organic vegetables.'
        ]);
    }
}
