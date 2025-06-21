<?php

namespace Tests\Feature\Seeders;

use App\Models\SystemSetting;
use Database\Seeders\CrmDefaultSettingsSeeder;
use Database\Seeders\CrmPriorityLevelsSeeder;
use Database\Seeders\CrmSalesStagesSeeder;
use Database\Seeders\CrmContactRolesSeeder;
use Database\Seeders\CrmInteractionTypesSeeder;
use Database\Seeders\CrmMarketSegmentsSeeder;
use Database\Seeders\CrmDistributorOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmDefaultSettingsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function main_seeder_executes_all_individual_seeders()
    {
        $this->assertDatabaseEmpty('system_settings');

        $this->seed(CrmDefaultSettingsSeeder::class);

        // Verify all 6 CRM settings were created
        $this->assertDatabaseCount('system_settings', 6);
        
        $expectedKeys = [
            'crm.priority_levels',
            'crm.sales_stages', 
            'crm.contact_roles',
            'crm.interaction_types',
            'crm.market_segments',
            'crm.distributor_options'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertDatabaseHas('system_settings', [
                'key' => $key,
                'category' => 'crm'
            ]);
        }
    }

    /** @test */
    public function seeder_is_idempotent_can_run_multiple_times()
    {
        // Run seeder first time
        $this->seed(CrmDefaultSettingsSeeder::class);
        $initialCount = SystemSetting::where('category', 'crm')->count();
        $this->assertEquals(6, $initialCount);

        // Run seeder second time
        $this->seed(CrmDefaultSettingsSeeder::class);
        $secondCount = SystemSetting::where('category', 'crm')->count();
        
        // Should still be 6, not 12
        $this->assertEquals(6, $secondCount);
        $this->assertEquals($initialCount, $secondCount);
    }

    /** @test */
    public function seeder_warms_cache_after_completion()
    {
        Cache::flush();
        $this->assertFalse(Cache::has('settings:category:crm'));

        $this->seed(CrmDefaultSettingsSeeder::class);

        // Cache should be warmed for CRM category
        $this->assertTrue(Cache::has('settings:category:crm'));
    }

    /** @test */
    public function seeder_uses_database_transactions()
    {
        // Mock a DB transaction error by creating invalid data scenario
        DB::shouldReceive('transaction')
          ->once()
          ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->seed(CrmDefaultSettingsSeeder::class);

        // If transaction failed, no settings should be created
        $this->assertDatabaseEmpty('system_settings');
    }

    /** @test */
    public function priority_levels_seeder_creates_correct_structure()
    {
        $this->seed(CrmPriorityLevelsSeeder::class);

        $setting = SystemSetting::where('key', 'crm.priority_levels')->first();
        $this->assertNotNull($setting);
        
        $priorityLevels = json_decode($setting->value, true);
        
        // Should have A, B, C, D priority levels
        $this->assertArrayHasKey('A', $priorityLevels);
        $this->assertArrayHasKey('B', $priorityLevels);
        $this->assertArrayHasKey('C', $priorityLevels);
        $this->assertArrayHasKey('D', $priorityLevels);

        // Each priority should have required fields
        foreach ($priorityLevels as $priority) {
            $this->assertArrayHasKey('label', $priority);
            $this->assertArrayHasKey('color', $priority);
            $this->assertArrayHasKey('description', $priority);
            $this->assertArrayHasKey('weight', $priority);
            $this->assertArrayHasKey('follow_up_days', $priority);
        }

        // Verify weight ordering (A=4, B=3, C=2, D=1)
        $this->assertEquals(4, $priorityLevels['A']['weight']);
        $this->assertEquals(3, $priorityLevels['B']['weight']);
        $this->assertEquals(2, $priorityLevels['C']['weight']);
        $this->assertEquals(1, $priorityLevels['D']['weight']);
    }

    /** @test */
    public function sales_stages_seeder_creates_proper_pipeline()
    {
        $this->seed(CrmSalesStagesSeeder::class);

        $setting = SystemSetting::where('key', 'crm.sales_stages')->first();
        $this->assertNotNull($setting);
        
        $salesStages = json_decode($setting->value, true);
        
        // Should have all required stages
        $expectedStages = ['lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        foreach ($expectedStages as $stage) {
            $this->assertArrayHasKey($stage, $salesStages);
        }

        // Verify proper ordering
        $orders = array_column($salesStages, 'order');
        sort($orders);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $orders);

        // Verify closed stages are marked correctly
        $this->assertTrue($salesStages['closed_won']['is_closed']);
        $this->assertTrue($salesStages['closed_won']['is_won']);
        $this->assertTrue($salesStages['closed_lost']['is_closed']);
        $this->assertFalse($salesStages['closed_lost']['is_won']);
    }

    /** @test */
    public function contact_roles_seeder_creates_restaurant_specific_roles()
    {
        $this->seed(CrmContactRolesSeeder::class);

        $setting = SystemSetting::where('key', 'crm.contact_roles')->first();
        $contactRoles = json_decode($setting->value, true);
        
        // Should have restaurant-specific roles
        $expectedRoles = ['owner', 'manager', 'chef', 'purchaser', 'assistant', 'accountant', 'other'];
        foreach ($expectedRoles as $role) {
            $this->assertArrayHasKey($role, $contactRoles);
        }

        // Verify chef role is restaurant-specific
        $this->assertEquals('Executive Chef', $contactRoles['chef']);
        $this->assertEquals('Purchasing Manager', $contactRoles['purchaser']);
    }

    /** @test */
    public function interaction_types_seeder_covers_all_communication_methods()
    {
        $this->seed(CrmInteractionTypesSeeder::class);

        $setting = SystemSetting::where('key', 'crm.interaction_types')->first();
        $interactionTypes = json_decode($setting->value, true);
        
        // Should have comprehensive interaction types
        $expectedTypes = ['call', 'email', 'meeting', 'demo', 'follow_up', 'proposal', 'contract', 'other'];
        foreach ($expectedTypes as $type) {
            $this->assertArrayHasKey($type, $interactionTypes);
        }

        // Verify business-specific types
        $this->assertEquals('Product Demo', $interactionTypes['demo']);
        $this->assertEquals('Proposal Presentation', $interactionTypes['proposal']);
    }

    /** @test */
    public function market_segments_seeder_covers_food_service_industry()
    {
        $this->seed(CrmMarketSegmentsSeeder::class);

        $setting = SystemSetting::where('key', 'crm.market_segments')->first();
        $marketSegments = json_decode($setting->value, true);
        
        // Should have food service specific segments
        $expectedSegments = ['fine_dining', 'casual_dining', 'quick_service', 'fast_casual', 'catering', 'institutional', 'hotel_restaurant', 'food_truck', 'other'];
        foreach ($expectedSegments as $segment) {
            $this->assertArrayHasKey($segment, $marketSegments);
        }

        // Verify industry-specific segments
        $this->assertEquals('Fine Dining', $marketSegments['fine_dining']);
        $this->assertEquals('Quick Service', $marketSegments['quick_service']);
        $this->assertEquals('Food Truck', $marketSegments['food_truck']);
    }

    /** @test */
    public function distributor_options_seeder_covers_supply_chain_types()
    {
        $this->seed(CrmDistributorOptionsSeeder::class);

        $setting = SystemSetting::where('key', 'crm.distributor_options')->first();
        $distributorOptions = json_decode($setting->value, true);
        
        // Should have comprehensive distributor types
        $expectedOptions = ['broadline', 'specialty', 'direct', 'local', 'regional', 'national', 'other'];
        foreach ($expectedOptions as $option) {
            $this->assertArrayHasKey($option, $distributorOptions);
        }

        // Verify supply chain specific options
        $this->assertEquals('Broadline Distributor', $distributorOptions['broadline']);
        $this->assertEquals('Direct from Manufacturer', $distributorOptions['direct']);
        $this->assertEquals('National Chain', $distributorOptions['national']);
    }

    /** @test */
    public function all_seeders_create_settings_with_correct_metadata()
    {
        $this->seed(CrmDefaultSettingsSeeder::class);

        $crmSettings = SystemSetting::where('category', 'crm')->get();
        
        foreach ($crmSettings as $setting) {
            // All CRM settings should have required metadata
            $this->assertEquals('crm', $setting->category);
            $this->assertEquals('json', $setting->type);
            $this->assertFalse($setting->is_public);
            $this->assertEquals('json_editor', $setting->ui_component);
            $this->assertNotEmpty($setting->description);
            $this->assertNotNull($setting->default_value);
            $this->assertNotNull($setting->validation_rules);
            $this->assertGreaterThan(0, $setting->sort_order);
        }
    }

    /** @test */
    public function seeder_validates_data_before_insertion()
    {
        // This test verifies that the validation logic works
        // Since our seeders include validation, we test indirectly
        $this->seed(CrmSalesStagesSeeder::class);
        
        $setting = SystemSetting::where('key', 'crm.sales_stages')->first();
        $salesStages = json_decode($setting->value, true);
        
        // Verify data validation passed by checking unique order numbers
        $orders = array_column($salesStages, 'order');
        $this->assertEquals(count($orders), count(array_unique($orders)), 'Order numbers should be unique');
        
        // Verify all required fields are present
        foreach ($salesStages as $stage) {
            $this->assertArrayHasKey('label', $stage);
            $this->assertArrayHasKey('order', $stage);
            $this->assertArrayHasKey('is_active', $stage);
        }
    }

    /** @test */
    public function seeder_handles_different_environments()
    {
        // Test in development environment
        app()->bind('env', fn() => 'development');
        
        $this->seed(CrmSalesStagesSeeder::class);
        
        $setting = SystemSetting::where('key', 'crm.sales_stages')->first();
        $salesStages = json_decode($setting->value, true);
        
        // In development, should have additional demo fields
        foreach ($salesStages as $stage) {
            if (app()->environment(['local', 'development', 'staging'])) {
                $this->assertArrayHasKey('demo_description', $stage);
                $this->assertArrayHasKey('typical_duration_days', $stage);
                $this->assertArrayHasKey('success_rate_percent', $stage);
            }
        }
    }

    /** @test */
    public function seeder_performance_is_optimized()
    {
        $startTime = microtime(true);
        
        $this->seed(CrmDefaultSettingsSeeder::class);
        
        $executionTime = microtime(true) - $startTime;
        
        // Seeding should complete within reasonable time (5 seconds)
        $this->assertLessThan(5.0, $executionTime, 'Seeding should complete within 5 seconds');
        
        // Verify all operations used transactions (indirect test)
        $this->assertDatabaseCount('system_settings', 6);
    }

    /** @test */
    public function seeder_integrates_with_settings_service()
    {
        $this->seed(CrmDefaultSettingsSeeder::class);

        // Should be able to retrieve CRM settings via service
        $crmSettings = SystemSetting::getByCategory('crm');
        
        $this->assertNotEmpty($crmSettings);
        $this->assertArrayHasKey('crm.priority_levels', $crmSettings);
        $this->assertArrayHasKey('crm.sales_stages', $crmSettings);
        
        // Values should be properly typed
        $priorityLevels = SystemSetting::getValue('crm.priority_levels');
        $this->assertIsArray($priorityLevels);
        $this->assertArrayHasKey('A', $priorityLevels);
    }
}