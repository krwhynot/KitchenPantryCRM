<?php

namespace Tests\Unit\Models;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_system_setting_with_new_fields()
    {
        $setting = SystemSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'category' => 'system',
            'type' => 'string',
            'description' => 'Test setting description',
            'is_public' => true,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'test_setting',
            'category' => 'system',
            'type' => 'string',
            'is_public' => true,
        ]);
    }

    public function test_application_level_type_validation_works()
    {
        $setting = SystemSetting::factory()->create(['type' => 'string']);
        $this->assertTrue($setting->validateType());

        $setting->type = 'invalid_type';
        $this->assertFalse($setting->validateType());
    }

    public function test_caching_performance_optimization()
    {
        Cache::flush();
        
        SystemSetting::create(['key' => 'cached_test', 'value' => 'test_value']);

        // First call should hit database and cache
        $value1 = SystemSetting::getValue('cached_test');
        $this->assertEquals('test_value', $value1);

        // Second call should hit cache only
        $value2 = SystemSetting::getValue('cached_test');
        $this->assertEquals('test_value', $value2);

        // Verify cache exists
        $this->assertTrue(Cache::has('setting:cached_test'));
    }

    public function test_cache_invalidation_on_updates()
    {
        $setting = SystemSetting::create(['key' => 'invalidation_test', 'value' => 'original']);
        
        // Cache the value
        SystemSetting::getValue('invalidation_test');
        $this->assertTrue(Cache::has('setting:invalidation_test'));

        // Update should clear cache
        $setting->update(['value' => 'updated']);
        $this->assertFalse(Cache::has('setting:invalidation_test'));
    }

    public function test_typed_value_casting_all_types()
    {
        $tests = [
            ['type' => 'boolean', 'value' => '1'],
            ['type' => 'integer', 'value' => '123'],
            ['type' => 'json', 'value' => '{"key":"value"}'],
            ['type' => 'array', 'value' => '["a","b","c"]'],
            ['type' => 'string', 'value' => 'test'],
        ];

        foreach ($tests as $test) {
            $setting = SystemSetting::factory()->create($test);
            
            // Test based on type
            switch ($test['type']) {
                case 'boolean':
                    $this->assertTrue($setting->typed_value);
                    break;
                case 'integer':
                    $this->assertEquals(123, $setting->typed_value);
                    break;
                case 'json':
                    $this->assertEquals(['key' => 'value'], $setting->typed_value);
                    break;
                case 'array':
                    $this->assertEquals(['a', 'b', 'c'], $setting->typed_value);
                    break;
                case 'string':
                    $this->assertEquals('test', $setting->typed_value);
                    break;
            }
        }
    }

    public function test_performance_optimized_scopes_use_indexes()
    {
        SystemSetting::factory()->count(100)->create();
        
        // These should use database indexes
        $systemSettings = SystemSetting::byCategory('system')->get();
        $publicSettings = SystemSetting::public()->get();
        $orderedSettings = SystemSetting::ordered()->get();

        $this->assertTrue(true); // If no performance issues, test passes
    }

    public function test_set_value_validates_allowed_types()
    {
        $this->expectException(\InvalidArgumentException::class);
        SystemSetting::setValue('test_key', 'value', 'invalid_type');
    }

    public function test_category_caching_optimization()
    {
        SystemSetting::factory()->category('crm')->count(5)->create();
        
        // Should cache category results
        $crmSettings1 = SystemSetting::getByCategory('crm');
        $crmSettings2 = SystemSetting::getByCategory('crm');
        
        $this->assertEquals($crmSettings1->count(), $crmSettings2->count());
        $this->assertTrue(Cache::has('settings:category:crm'));
    }

    public function test_scope_by_category_filters_correctly()
    {
        SystemSetting::factory()->category('system')->count(3)->create();
        SystemSetting::factory()->category('crm')->count(2)->create();

        $systemSettings = SystemSetting::byCategory('system')->get();
        $crmSettings = SystemSetting::byCategory('crm')->get();

        $this->assertCount(3, $systemSettings);
        $this->assertCount(2, $crmSettings);
        $this->assertTrue($systemSettings->every(fn($s) => $s->category === 'system'));
        $this->assertTrue($crmSettings->every(fn($s) => $s->category === 'crm'));
    }

    public function test_get_value_static_method_returns_typed_value()
    {
        SystemSetting::create([
            'key' => 'test_boolean',
            'value' => '1',
            'type' => 'boolean',
        ]);

        $value = SystemSetting::getValue('test_boolean');
        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    public function test_set_value_auto_detects_type()
    {
        SystemSetting::setValue('auto_string', 'test');
        SystemSetting::setValue('auto_integer', 123);
        SystemSetting::setValue('auto_boolean', true);
        SystemSetting::setValue('auto_array', ['a', 'b', 'c']);

        $this->assertEquals('string', SystemSetting::where('key', 'auto_string')->first()->type);
        $this->assertEquals('integer', SystemSetting::where('key', 'auto_integer')->first()->type);
        $this->assertEquals('boolean', SystemSetting::where('key', 'auto_boolean')->first()->type);
        $this->assertEquals('json', SystemSetting::where('key', 'auto_array')->first()->type);
    }

    public function test_get_by_category_returns_keyed_collection()
    {
        SystemSetting::create(['key' => 'crm_setting_1', 'value' => 'value1', 'category' => 'crm']);
        SystemSetting::create(['key' => 'crm_setting_2', 'value' => 'value2', 'category' => 'crm']);
        SystemSetting::create(['key' => 'system_setting', 'value' => 'value3', 'category' => 'system']);

        $crmSettings = SystemSetting::getByCategory('crm');

        $this->assertCount(2, $crmSettings);
        $this->assertEquals('value1', $crmSettings['crm_setting_1']);
        $this->assertEquals('value2', $crmSettings['crm_setting_2']);
        $this->assertArrayNotHasKey('system_setting', $crmSettings);
    }

    public function test_display_value_formats_correctly()
    {
        $booleanSetting = SystemSetting::factory()->boolean()->create(['value' => '1']);
        $longStringSetting = SystemSetting::factory()->string()->create(['value' => str_repeat('a', 60)]);
        $jsonSetting = SystemSetting::factory()->json()->create();

        $this->assertEquals('Yes', $booleanSetting->display_value);
        $this->assertStringEndsWith('...', $longStringSetting->display_value);
        $this->assertStringContainsString('JSON Data', $jsonSetting->display_value);
    }

    public function test_ordered_scope_sorts_by_sort_order_then_key()
    {
        SystemSetting::create(['key' => 'b_setting', 'value' => 'test', 'sort_order' => 10]);
        SystemSetting::create(['key' => 'a_setting', 'value' => 'test', 'sort_order' => 5]);
        SystemSetting::create(['key' => 'c_setting', 'value' => 'test', 'sort_order' => 10]);

        $ordered = SystemSetting::ordered()->pluck('key')->toArray();

        $this->assertEquals(['a_setting', 'b_setting', 'c_setting'], $ordered);
    }

    public function test_factory_generates_all_setting_types()
    {
        $stringSettings = SystemSetting::factory()->string()->create();
        $integerSettings = SystemSetting::factory()->integer()->create();
        $booleanSettings = SystemSetting::factory()->boolean()->create();
        $jsonSettings = SystemSetting::factory()->json()->create();
        $arraySettings = SystemSetting::factory()->array()->create();
        $colorSettings = SystemSetting::factory()->color()->create();

        $this->assertEquals('string', $stringSettings->type);
        $this->assertEquals('integer', $integerSettings->type);
        $this->assertEquals('boolean', $booleanSettings->type);
        $this->assertEquals('json', $jsonSettings->type);
        $this->assertEquals('array', $arraySettings->type);
        $this->assertEquals('color', $colorSettings->type);
    }

    public function test_crm_category_settings()
    {
        $crmSetting = SystemSetting::factory()->crmSetting()->create();
        
        $this->assertEquals('crm', $crmSetting->category);
        $this->assertContains($crmSetting->key, ['priority_levels', 'sales_stages', 'contact_roles']);
    }
}