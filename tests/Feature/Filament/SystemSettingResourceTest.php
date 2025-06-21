<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_system_settings()
    {
        $settings = SystemSetting::factory()->count(5)->create();

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($settings);
    }

    public function test_can_create_system_setting()
    {
        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm([
                'key' => 'test.setting',
                'value' => 'test value',
                'category' => 'system',
                'type' => 'string',
                'description' => 'Test setting description',
                'is_public' => false,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('system_settings', [
            'key' => 'test.setting',
            'value' => 'test value',
            'category' => 'system',
            'type' => 'string',
        ]);
    }

    public function test_can_edit_system_setting()
    {
        $setting = SystemSetting::factory()->create([
            'key' => 'edit.test',
            'value' => 'original value',
            'type' => 'string',
        ]);

        Livewire::test(SystemSettingResource\Pages\EditSystemSetting::class, [
            'record' => $setting->getRouteKey(),
        ])
        ->fillForm([
            'value' => 'updated value',
            'description' => 'Updated description',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'value' => 'updated value',
        ]);
    }

    public function test_can_delete_system_setting()
    {
        $setting = SystemSetting::factory()->create();

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->callTableAction('delete', $setting);

        $this->assertDatabaseMissing('system_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_dynamic_form_fields_for_different_types()
    {
        // Test string type
        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm(['type' => 'string'])
            ->assertFormFieldExists('value')
            ->assertFormFieldIsVisible('value');

        // Test boolean type
        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm(['type' => 'boolean'])
            ->assertFormFieldExists('value')
            ->assertFormFieldIsVisible('value');

        // Test integer type
        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm(['type' => 'integer'])
            ->assertFormFieldExists('value')
            ->assertFormFieldIsVisible('value');
    }

    public function test_can_filter_by_category()
    {
        $systemSetting = SystemSetting::factory()->create(['category' => 'system']);
        $crmSetting = SystemSetting::factory()->create(['category' => 'crm']);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->filterTable('category', 'system')
            ->assertCanSeeTableRecords([$systemSetting])
            ->assertCanNotSeeTableRecords([$crmSetting]);
    }

    public function test_can_filter_by_type()
    {
        $stringSetting = SystemSetting::factory()->create(['type' => 'string']);
        $booleanSetting = SystemSetting::factory()->create(['type' => 'boolean']);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->filterTable('type', 'string')
            ->assertCanSeeTableRecords([$stringSetting])
            ->assertCanNotSeeTableRecords([$booleanSetting]);
    }

    public function test_can_filter_by_visibility()
    {
        $publicSetting = SystemSetting::factory()->create(['is_public' => true]);
        $privateSetting = SystemSetting::factory()->create(['is_public' => false]);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->filterTable('is_public', true)
            ->assertCanSeeTableRecords([$publicSetting])
            ->assertCanNotSeeTableRecords([$privateSetting]);
    }

    public function test_can_search_settings()
    {
        $setting1 = SystemSetting::factory()->create(['key' => 'app.name']);
        $setting2 = SystemSetting::factory()->create(['key' => 'app.version']);
        $setting3 = SystemSetting::factory()->create(['key' => 'database.host']);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->searchTable('app')
            ->assertCanSeeTableRecords([$setting1, $setting2])
            ->assertCanNotSeeTableRecords([$setting3]);
    }

    public function test_can_toggle_public_visibility()
    {
        $setting = SystemSetting::factory()->create(['is_public' => false]);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->callTableColumnAction('is_public', $setting);

        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'is_public' => true,
        ]);
    }

    public function test_can_bulk_delete_settings()
    {
        $settings = SystemSetting::factory()->count(3)->create();

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->callTableBulkAction('delete', $settings);

        foreach ($settings as $setting) {
            $this->assertDatabaseMissing('system_settings', [
                'id' => $setting->id,
            ]);
        }
    }

    public function test_can_bulk_toggle_public_settings()
    {
        $settings = SystemSetting::factory()->count(3)->create(['is_public' => false]);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->callTableBulkAction('toggle_public', $settings);

        foreach ($settings as $setting) {
            $this->assertDatabaseHas('system_settings', [
                'id' => $setting->id,
                'is_public' => true,
            ]);
        }
    }

    public function test_table_displays_formatted_values()
    {
        $booleanSetting = SystemSetting::factory()->create([
            'type' => 'boolean',
            'value' => '1',
        ]);

        $colorSetting = SystemSetting::factory()->create([
            'type' => 'color',
            'value' => '#ff0000',
        ]);

        $jsonSetting = SystemSetting::factory()->create([
            'type' => 'json',
            'value' => '{"test": "value"}',
        ]);

        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->assertCanSeeTableRecords([$booleanSetting, $colorSetting, $jsonSetting])
            ->assertSee('✓ Yes') // Boolean formatting
            ->assertSee('#ff0000') // Color value
            ->assertSee('JSON (1 items)'); // JSON formatting
    }

    public function test_form_validation_works()
    {
        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm([
                'key' => '', // Required field
                'type' => '', // Required field
                'category' => '', // Required field
            ])
            ->call('create')
            ->assertHasFormErrors(['key', 'type', 'category']);
    }

    public function test_unique_key_validation()
    {
        $existingSetting = SystemSetting::factory()->create(['key' => 'unique.test']);

        Livewire::test(SystemSettingResource\Pages\CreateSystemSetting::class)
            ->fillForm([
                'key' => 'unique.test', // Duplicate key
                'value' => 'test',
                'category' => 'system',
                'type' => 'string',
            ])
            ->call('create')
            ->assertHasFormErrors(['key']);
    }

    public function test_header_actions_exist()
    {
        Livewire::test(SystemSettingResource\Pages\ListSystemSettings::class)
            ->assertActionExists('setup_crm_defaults')
            ->assertActionExists('cache_settings')
            ->assertActionExists('create');
    }
}