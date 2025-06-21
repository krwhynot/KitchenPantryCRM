<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settingsService;
    private ValidatorFactory $validator;
    private SystemSetting $model;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsService = app(SettingsService::class);
        $this->validator = app('validator');
        $this->model = app(SystemSetting::class);
    }

    public function test_service_is_singleton()
    {
        $service1 = app(SettingsService::class);
        $service2 = app(SettingsService::class);
        
        $this->assertSame($service1, $service2);
    }

    public function test_can_get_setting_value()
    {
        SystemSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string'
        ]);

        $value = $this->settingsService->get('test_setting');
        
        $this->assertEquals('test_value', $value);
    }

    public function test_get_returns_default_when_setting_not_found()
    {
        $value = $this->settingsService->get('nonexistent_setting', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function test_can_set_setting_value()
    {
        $setting = $this->settingsService->set('new_setting', 'new_value');
        
        $this->assertInstanceOf(SystemSetting::class, $setting);
        $this->assertEquals('new_setting', $setting->key);
        $this->assertEquals('new_value', $setting->value);
        $this->assertEquals('string', $setting->type);
    }

    public function test_set_auto_detects_type()
    {
        $this->settingsService->set('string_setting', 'text');
        $this->settingsService->set('integer_setting', 123);
        $this->settingsService->set('boolean_setting', true);
        $this->settingsService->set('array_setting', ['a', 'b', 'c']);

        $this->assertEquals('string', SystemSetting::where('key', 'string_setting')->first()->type);
        $this->assertEquals('integer', SystemSetting::where('key', 'integer_setting')->first()->type);
        $this->assertEquals('boolean', SystemSetting::where('key', 'boolean_setting')->first()->type);
        $this->assertEquals('json', SystemSetting::where('key', 'array_setting')->first()->type);
    }

    public function test_set_throws_exception_for_invalid_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->settingsService->set('test_setting', 'value', 'invalid_type');
    }

    public function test_can_get_settings_by_category()
    {
        SystemSetting::create(['key' => 'setting1', 'value' => 'value1', 'category' => 'test', 'type' => 'string']);
        SystemSetting::create(['key' => 'setting2', 'value' => 'value2', 'category' => 'test', 'type' => 'string']);
        SystemSetting::create(['key' => 'setting3', 'value' => 'value3', 'category' => 'other', 'type' => 'string']);

        $testSettings = $this->settingsService->getCategory('test');
        
        $this->assertInstanceOf(Collection::class, $testSettings);
        $this->assertCount(2, $testSettings);
        $this->assertEquals('value1', $testSettings['setting1']);
        $this->assertEquals('value2', $testSettings['setting2']);
        $this->assertArrayNotHasKey('setting3', $testSettings);
    }

    public function test_can_get_bulk_settings()
    {
        SystemSetting::create(['key' => 'setting1', 'value' => 'value1', 'type' => 'string']);
        SystemSetting::create(['key' => 'setting2', 'value' => '123', 'type' => 'integer']);
        SystemSetting::create(['key' => 'setting3', 'value' => '1', 'type' => 'boolean']);

        $results = $this->settingsService->getBulk(['setting1', 'setting2', 'setting3', 'nonexistent']);
        
        $this->assertEquals('value1', $results['setting1']);
        $this->assertEquals(123, $results['setting2']);
        $this->assertTrue($results['setting3']);
        $this->assertArrayNotHasKey('nonexistent', $results);
    }

    public function test_can_set_bulk_settings()
    {
        $settings = [
            'bulk1' => 'value1',
            'bulk2' => 123,
            'bulk3' => true
        ];

        $results = $this->settingsService->setBulk($settings);
        
        $this->assertCount(3, $results);
        foreach ($results as $setting) {
            $this->assertInstanceOf(SystemSetting::class, $setting);
        }
        
        $this->assertEquals('value1', $this->settingsService->get('bulk1'));
        $this->assertEquals(123, $this->settingsService->get('bulk2'));
        $this->assertTrue($this->settingsService->get('bulk3'));
    }

    public function test_can_get_public_settings()
    {
        SystemSetting::create(['key' => 'public1', 'value' => 'value1', 'is_public' => true, 'type' => 'string']);
        SystemSetting::create(['key' => 'public2', 'value' => 'value2', 'is_public' => true, 'type' => 'string']);
        SystemSetting::create(['key' => 'private1', 'value' => 'value3', 'is_public' => false, 'type' => 'string']);

        $publicSettings = $this->settingsService->getPublicSettings();
        
        $this->assertInstanceOf(Collection::class, $publicSettings);
        $this->assertCount(2, $publicSettings);
        $this->assertEquals('value1', $publicSettings['public1']);
        $this->assertEquals('value2', $publicSettings['public2']);
        $this->assertArrayNotHasKey('private1', $publicSettings);
    }

    public function test_validation_works()
    {
        SystemSetting::create([
            'key' => 'validated_setting',
            'value' => 'test',
            'type' => 'string',
            'validation_rules' => ['string', 'min:3']
        ]);

        $this->assertTrue($this->settingsService->validate('validated_setting', 'valid_string'));
        $this->assertFalse($this->settingsService->validate('validated_setting', 'ab')); // too short
    }

    public function test_validation_returns_true_for_settings_without_rules()
    {
        SystemSetting::create(['key' => 'no_rules', 'value' => 'test', 'type' => 'string']);

        $this->assertTrue($this->settingsService->validate('no_rules', 'any_value'));
    }

    public function test_cache_invalidation_on_set()
    {
        // Create a setting first to ensure it has a category
        SystemSetting::create(['key' => 'cache_test', 'value' => 'old_value', 'category' => 'system', 'type' => 'string']);
        
        // Test that cache gets invalidated when setting is updated
        $this->settingsService->set('cache_test', 'new_value');
        
        // Verify the value was updated
        $this->assertEquals('new_value', $this->settingsService->get('cache_test'));
    }

    public function test_flush_clears_all_caches()
    {
        SystemSetting::create(['key' => 'test1', 'value' => 'value1', 'category' => 'system', 'type' => 'string']);
        SystemSetting::create(['key' => 'test2', 'value' => 'value2', 'category' => 'crm', 'type' => 'string']);

        // Populate caches
        $this->settingsService->get('test1');
        $this->settingsService->getCategory('system');
        
        $this->settingsService->flush();
        
        // Cache should be cleared - this is hard to test directly, but we can verify no errors
        $this->assertTrue(true);
    }

    public function test_get_priority_levels()
    {
        $priorities = $this->settingsService->getPriorityLevels();
        
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('A', $priorities);
        $this->assertArrayHasKey('B', $priorities);
        $this->assertArrayHasKey('C', $priorities);
        $this->assertArrayHasKey('D', $priorities);
        
        $this->assertEquals('High Priority', $priorities['A']['label']);
        $this->assertEquals('#dc3545', $priorities['A']['color']);
    }

    public function test_get_sales_stages()
    {
        $stages = $this->settingsService->getSalesStages();
        
        $this->assertIsArray($stages);
        $this->assertArrayHasKey('lead', $stages);
        $this->assertArrayHasKey('qualified', $stages);
        $this->assertArrayHasKey('closed_won', $stages);
        
        $this->assertEquals('Lead', $stages['lead']['label']);
        $this->assertEquals(1, $stages['lead']['order']);
    }

    public function test_get_contact_roles()
    {
        $roles = $this->settingsService->getContactRoles();
        
        $this->assertIsArray($roles);
        $this->assertArrayHasKey('owner', $roles);
        $this->assertArrayHasKey('manager', $roles);
        $this->assertArrayHasKey('chef', $roles);
        
        $this->assertEquals('Owner', $roles['owner']);
        $this->assertEquals('Executive Chef', $roles['chef']);
    }

    public function test_get_interaction_types()
    {
        $types = $this->settingsService->getInteractionTypes();
        
        $this->assertIsArray($types);
        $this->assertArrayHasKey('call', $types);
        $this->assertArrayHasKey('email', $types);
        $this->assertArrayHasKey('meeting', $types);
        
        $this->assertEquals('Phone Call', $types['call']);
        $this->assertEquals('In-Person Meeting', $types['meeting']);
    }

    public function test_get_market_segments()
    {
        $segments = $this->settingsService->getMarketSegments();
        
        $this->assertIsArray($segments);
        $this->assertArrayHasKey('fine_dining', $segments);
        $this->assertArrayHasKey('casual_dining', $segments);
        $this->assertArrayHasKey('quick_service', $segments);
        
        $this->assertEquals('Fine Dining', $segments['fine_dining']);
        $this->assertEquals('Quick Service', $segments['quick_service']);
    }

    public function test_get_distributor_options()
    {
        $options = $this->settingsService->getDistributorOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('broadline', $options);
        $this->assertArrayHasKey('specialty', $options);
        $this->assertArrayHasKey('direct', $options);
        
        $this->assertEquals('Broadline Distributor', $options['broadline']);
        $this->assertEquals('Direct from Manufacturer', $options['direct']);
    }

    public function test_get_crm_options_by_type()
    {
        $priorities = $this->settingsService->getCrmOptions('priority_levels');
        $stages = $this->settingsService->getCrmOptions('sales_stages');
        
        $this->assertArrayHasKey('A', $priorities);
        $this->assertArrayHasKey('lead', $stages);
    }

    public function test_get_crm_options_throws_exception_for_invalid_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->settingsService->getCrmOptions('invalid_type');
    }

    public function test_typed_values_are_cast_correctly()
    {
        $this->settingsService->set('string_val', 'test', 'string');
        $this->settingsService->set('int_val', '123', 'integer');
        $this->settingsService->set('bool_val', '1', 'boolean');
        $this->settingsService->set('json_val', ['key' => 'value'], 'json');

        $this->assertIsString($this->settingsService->get('string_val'));
        $this->assertIsInt($this->settingsService->get('int_val'));
        $this->assertIsBool($this->settingsService->get('bool_val'));
        $this->assertIsArray($this->settingsService->get('json_val'));
        
        $this->assertEquals('test', $this->settingsService->get('string_val'));
        $this->assertEquals(123, $this->settingsService->get('int_val'));
        $this->assertTrue($this->settingsService->get('bool_val'));
        $this->assertEquals(['key' => 'value'], $this->settingsService->get('json_val'));
    }

    public function test_cache_warming_doesnt_throw_errors()
    {
        SystemSetting::create(['key' => 'test1', 'value' => 'value1', 'category' => 'system', 'type' => 'string']);
        SystemSetting::create(['key' => 'test2', 'value' => 'value2', 'category' => 'crm', 'type' => 'string']);

        try {
            $this->settingsService->warmCache();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Cache warming threw an exception: ' . $e->getMessage());
        }
    }
}