<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CrmDefaultSettingsSeeder extends Seeder
{
    /**
     * Main orchestrator for CRM default configuration seeding.
     * 
     * This seeder coordinates the execution of all CRM-related configuration
     * seeders in the proper order to ensure data consistency and dependencies.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting CRM Default Settings Seeding...');
        
        // Track timing for performance monitoring
        $startTime = microtime(true);
        
        // Execute seeders in dependency order
        $this->call([
            CrmPriorityLevelsSeeder::class,
            CrmSalesStagesSeeder::class,
            CrmContactRolesSeeder::class,
            CrmInteractionTypesSeeder::class,
            CrmMarketSegmentsSeeder::class,
            CrmDistributorOptionsSeeder::class,
        ]);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info("✅ CRM Default Settings Seeding completed in {$duration} seconds");
        
        // Warm cache after seeding if SettingsService is available
        if (app()->bound(\App\Services\SettingsService::class)) {
            $this->command->info('🔥 Warming settings cache...');
            app(\App\Services\SettingsService::class)->warmCache();
            $this->command->info('✅ Cache warmed successfully');
        }
    }
}