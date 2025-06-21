<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Settings Facade
 * 
 * Provides a clean static API for the SettingsService
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static \App\Models\SystemSetting set(string $key, mixed $value, ?string $type = null)
 * @method static \Illuminate\Support\Collection getCategory(string $category)
 * @method static array getBulk(array $keys)
 * @method static array setBulk(array $settings)
 * @method static \Illuminate\Support\Collection getPublicSettings()
 * @method static bool validate(string $key, mixed $value)
 * @method static void flush()
 * @method static void warmCache()
 * @method static array getPriorityLevels()
 * @method static array getSalesStages()
 * @method static array getContactRoles()
 * @method static array getInteractionTypes()
 * @method static array getMarketSegments()
 * @method static array getDistributorOptions()
 * @method static array getCrmOptions(string $type)
 * 
 * @see \App\Services\SettingsService
 */
class Settings extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \App\Services\SettingsService::class;
    }
}