<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for the settings service.
    |
    */

    'cache_ttl' => env('SETTINGS_CACHE_TTL', 3600), // 1 hour default

    'cache_driver' => env('SETTINGS_CACHE_DRIVER', 'default'), // Use app default cache driver

    'cache_prefix' => env('SETTINGS_CACHE_PREFIX', 'settings'),

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings for the settings service.
    |
    */

    'warm_cache_on_boot' => env('SETTINGS_WARM_CACHE', true),

    'bulk_operations_enabled' => env('SETTINGS_BULK_OPERATIONS', true),

    'lazy_loading' => env('SETTINGS_LAZY_LOADING', true),

    /*
    |--------------------------------------------------------------------------
    | CRM Default Settings
    |--------------------------------------------------------------------------
    |
    | Default CRM configuration options that will be created if they don't exist.
    |
    */

    'crm_defaults' => [
        'priority_levels' => [
            'A' => ['label' => 'High Priority', 'color' => '#dc3545'],
            'B' => ['label' => 'Medium-High Priority', 'color' => '#fd7e14'],
            'C' => ['label' => 'Medium Priority', 'color' => '#ffc107'],
            'D' => ['label' => 'Low Priority', 'color' => '#28a745']
        ],

        'sales_stages' => [
            'lead' => ['label' => 'Lead', 'order' => 1],
            'qualified' => ['label' => 'Qualified', 'order' => 2],
            'proposal' => ['label' => 'Proposal Sent', 'order' => 3],
            'negotiation' => ['label' => 'Negotiation', 'order' => 4],
            'closed_won' => ['label' => 'Closed Won', 'order' => 5],
            'closed_lost' => ['label' => 'Closed Lost', 'order' => 6]
        ],

        'contact_roles' => [
            'owner' => 'Owner',
            'manager' => 'Manager',
            'chef' => 'Executive Chef',
            'purchaser' => 'Purchasing Manager',
            'assistant' => 'Assistant Manager',
            'accountant' => 'Accountant',
            'other' => 'Other'
        ],

        'interaction_types' => [
            'call' => 'Phone Call',
            'email' => 'Email',
            'meeting' => 'In-Person Meeting',
            'demo' => 'Product Demo',
            'follow_up' => 'Follow Up',
            'proposal' => 'Proposal Presentation',
            'contract' => 'Contract Discussion',
            'other' => 'Other'
        ],

        'market_segments' => [
            'fine_dining' => 'Fine Dining',
            'casual_dining' => 'Casual Dining',
            'quick_service' => 'Quick Service',
            'fast_casual' => 'Fast Casual',
            'catering' => 'Catering',
            'institutional' => 'Institutional',
            'hotel_restaurant' => 'Hotel Restaurant',
            'food_truck' => 'Food Truck',
            'other' => 'Other'
        ],

        'distributor_options' => [
            'broadline' => 'Broadline Distributor',
            'specialty' => 'Specialty Distributor',
            'direct' => 'Direct from Manufacturer',
            'local' => 'Local Distributor',
            'regional' => 'Regional Distributor',
            'national' => 'National Chain',
            'other' => 'Other'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    |
    | System-level settings configuration.
    |
    */

    'system_defaults' => [
        'app_name' => env('APP_NAME', 'PantryCRM'),
        'app_timezone' => env('APP_TIMEZONE', 'UTC'),
        'items_per_page' => 25,
        'max_file_upload_size' => '10MB',
        'session_timeout' => 120, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for different setting types.
    |
    */

    'validation_rules' => [
        'string' => ['string', 'max:255'],
        'integer' => ['integer', 'min:0'],
        'boolean' => ['boolean'],
        'json' => ['json'],
        'array' => ['array'],
        'color' => ['string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'email' => ['email'],
        'url' => ['url'],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Component Mapping
    |--------------------------------------------------------------------------
    |
    | Maps setting types to appropriate UI components for the admin interface.
    |
    */

    'ui_components' => [
        'string' => 'text_input',
        'integer' => 'number_input',
        'boolean' => 'toggle',
        'json' => 'json_editor',
        'array' => 'list_editor',
        'color' => 'color_picker',
        'select' => 'select_dropdown',
        'textarea' => 'textarea',
        'email' => 'email_input',
        'url' => 'url_input',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the settings service.
    |
    */

    'security' => [
        'encrypt_sensitive_values' => env('SETTINGS_ENCRYPT_SENSITIVE', false),
        'allowed_public_categories' => ['system', 'user'],
        'restricted_keys' => ['database', 'cache', 'session', 'app_key'],
        'audit_changes' => env('SETTINGS_AUDIT_CHANGES', true),
    ],

];