<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\InteractionSeeder;
use Database\Seeders\OpportunitySeeder;
use Database\Seeders\LeadSeeder;
use Database\Seeders\ContractSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            ContactSeeder::class,
            InteractionSeeder::class,
            OpportunitySeeder::class,
            LeadSeeder::class,
            ContractSeeder::class,
        ]);
    }
}
