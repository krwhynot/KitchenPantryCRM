<?php

use App\Services\SettingsService;
use Illuminate\Support\Facades\App;

if (!function_exists('setting')) {
    /**
     * Get or set a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $settingsService = App::make(SettingsService::class);
        
        return $settingsService->get($key, $default);
    }
}

if (!function_exists('crm_options')) {
    /**
     * Get CRM options by type
     *
     * @param string $type
     * @return array
     */
    function crm_options(string $type): array
    {
        $settingsService = App::make(SettingsService::class);
        
        return $settingsService->getCrmOptions($type);
    }
}

if (!function_exists('priority_levels')) {
    /**
     * Get priority levels with colors
     *
     * @return array
     */
    function priority_levels(): array
    {
        return crm_options('priority_levels');
    }
}

if (!function_exists('sales_stages')) {
    /**
     * Get sales pipeline stages
     *
     * @return array
     */
    function sales_stages(): array
    {
        return crm_options('sales_stages');
    }
}

if (!function_exists('contact_roles')) {
    /**
     * Get contact roles
     *
     * @return array
     */
    function contact_roles(): array
    {
        return crm_options('contact_roles');
    }
}

if (!function_exists('interaction_types')) {
    /**
     * Get interaction types
     *
     * @return array
     */
    function interaction_types(): array
    {
        return crm_options('interaction_types');
    }
}

if (!function_exists('market_segments')) {
    /**
     * Get market segments
     *
     * @return array
     */
    function market_segments(): array
    {
        return crm_options('market_segments');
    }
}

if (!function_exists('distributor_options')) {
    /**
     * Get distributor options
     *
     * @return array
     */
    function distributor_options(): array
    {
        return crm_options('distributor_options');
    }
}