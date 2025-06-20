<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\ContactSeeder;

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
        ]);
    }
}
