<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SettingsService
{
    private CacheManager $cache;
    private ValidatorFactory $validator;
    private SystemSetting $model;
    private int $cacheTtl;

    public function __construct(
        CacheManager $cacheManager,
        ValidatorFactory $validator,
        SystemSetting $model
    ) {
        $this->cache = $cacheManager;
        $this->validator = $validator;
        $this->model = $model;
        $this->cacheTtl = config('settings.cache_ttl', 3600);
    }

    /**
     * Get a single setting value with caching
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key}";
        
        return $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            $setting = $this->model->where('key', $key)->first();
            
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Set a setting value with cache invalidation
     */
    public function set(string $key, mixed $value, ?string $type = null): SystemSetting
    {
        $type = $type ?? $this->detectType($value);
        
        if (!array_key_exists($type, SystemSetting::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Invalid setting type: {$type}");
        }

        $setting = $this->model->updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->prepareValue($value, $type),
                'type' => $type
            ]
        );

        // Invalidate related caches
        $this->invalidateCache($key, $setting->category);

        return $setting;
    }

    /**
     * Get all settings in a category with caching
     */
    public function getCategory(string $category): Collection
    {
        $cacheKey = "settings:category:{$category}";
        
        return $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($category) {
            return $this->model->byCategory($category)
                ->ordered()
                ->get()
                ->mapWithKeys(fn($setting) => [$setting->key => $setting->typed_value]);
        });
    }

    /**
     * Get multiple settings in a single operation
     */
    public function getBulk(array $keys): array
    {
        $results = [];
        $uncachedKeys = [];
        
        // Check cache first
        foreach ($keys as $key) {
            $cacheKey = "setting:{$key}";
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== null) {
                $results[$key] = $cached;
            } else {
                $uncachedKeys[] = $key;
            }
        }
        
        // Fetch uncached settings from database
        if (!empty($uncachedKeys)) {
            $settings = $this->model->whereIn('key', $uncachedKeys)->get();
            
            foreach ($settings as $setting) {
                $value = $setting->typed_value;
                $results[$setting->key] = $value;
                
                // Cache individual setting
                $this->cache->put("setting:{$setting->key}", $value, $this->cacheTtl);
            }
        }
        
        return $results;
    }

    /**
     * Set multiple settings in bulk
     */
    public function setBulk(array $settings): array
    {
        $results = [];
        
        foreach ($settings as $key => $value) {
            $results[$key] = $this->set($key, $value);
        }
        
        return $results;
    }

    /**
     * Get only public settings (user-visible)
     */
    public function getPublicSettings(): Collection
    {
        $cacheKey = 'settings:public';
        
        return $this->cache->remember($cacheKey, $this->cacheTtl, function () {
            return $this->model->public()
                ->ordered()
                ->get()
                ->mapWithKeys(fn($setting) => [$setting->key => $setting->typed_value]);
        });
    }

    /**
     * Validate a setting value
     */
    public function validate(string $key, mixed $value): bool
    {
        $setting = $this->model->where('key', $key)->first();
        
        if (!$setting || !$setting->validation_rules) {
            return true;
        }
        
        $validator = $this->validator->make(
            ['value' => $value],
            ['value' => $setting->validation_rules]
        );
        
        return !$validator->fails();
    }

    /**
     * Clear all settings cache
     */
    public function flush(): void
    {
        // Clear individual setting caches
        $keys = $this->model->pluck('key');
        foreach ($keys as $key) {
            $this->cache->forget("setting:{$key}");
        }
        
        // Clear category caches
        $categories = array_keys(SystemSetting::ALLOWED_CATEGORIES);
        foreach ($categories as $category) {
            $this->cache->forget("settings:category:{$category}");
        }
        
        // Clear other cached collections
        $this->cache->forget('settings:public');
        $this->cache->forget('settings:all');
    }

    /**
     * Warm cache with frequently accessed settings
     */
    public function warmCache(): void
    {
        // Warm category caches
        foreach (array_keys(SystemSetting::ALLOWED_CATEGORIES) as $category) {
            $this->getCategory($category);
        }
        
        // Warm public settings
        $this->getPublicSettings();
    }

    // CRM-Specific Helper Methods

    /**
     * Get priority levels with colors (A-D)
     */
    public function getPriorityLevels(): array
    {
        $cached = $this->get('crm.priority_levels');
        
        if ($cached) {
            return $cached;
        }
        
        // Default CRM priority levels
        $defaults = [
            'A' => ['label' => 'High Priority', 'color' => '#dc3545'],
            'B' => ['label' => 'Medium-High Priority', 'color' => '#fd7e14'],
            'C' => ['label' => 'Medium Priority', 'color' => '#ffc107'],
            'D' => ['label' => 'Low Priority', 'color' => '#28a745']
        ];
        
        $this->set('crm.priority_levels', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get sales pipeline stages
     */
    public function getSalesStages(): array
    {
        $cached = $this->get('crm.sales_stages');
        
        if ($cached) {
            return $cached;
        }
        
        $defaults = [
            'lead' => ['label' => 'Lead', 'order' => 1],
            'qualified' => ['label' => 'Qualified', 'order' => 2],
            'proposal' => ['label' => 'Proposal Sent', 'order' => 3],
            'negotiation' => ['label' => 'Negotiation', 'order' => 4],
            'closed_won' => ['label' => 'Closed Won', 'order' => 5],
            'closed_lost' => ['label' => 'Closed Lost', 'order' => 6]
        ];
        
        $this->set('crm.sales_stages', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get contact roles
     */
    public function getContactRoles(): array
    {
        $cached = $this->get('crm.contact_roles');
        
        if ($cached) {
            return $cached;
        }
        
        $defaults = [
            'owner' => 'Owner',
            'manager' => 'Manager', 
            'chef' => 'Executive Chef',
            'purchaser' => 'Purchasing Manager',
            'assistant' => 'Assistant Manager',
            'accountant' => 'Accountant',
            'other' => 'Other'
        ];
        
        $this->set('crm.contact_roles', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get interaction types
     */
    public function getInteractionTypes(): array
    {
        $cached = $this->get('crm.interaction_types');
        
        if ($cached) {
            return $cached;
        }
        
        $defaults = [
            'call' => 'Phone Call',
            'email' => 'Email',
            'meeting' => 'In-Person Meeting',
            'demo' => 'Product Demo',
            'follow_up' => 'Follow Up',
            'proposal' => 'Proposal Presentation',
            'contract' => 'Contract Discussion',
            'other' => 'Other'
        ];
        
        $this->set('crm.interaction_types', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get market segments
     */
    public function getMarketSegments(): array
    {
        $cached = $this->get('crm.market_segments');
        
        if ($cached) {
            return $cached;
        }
        
        $defaults = [
            'fine_dining' => 'Fine Dining',
            'casual_dining' => 'Casual Dining',
            'quick_service' => 'Quick Service',
            'fast_casual' => 'Fast Casual',
            'catering' => 'Catering',
            'institutional' => 'Institutional',
            'hotel_restaurant' => 'Hotel Restaurant',
            'food_truck' => 'Food Truck',
            'other' => 'Other'
        ];
        
        $this->set('crm.market_segments', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get distributor options
     */
    public function getDistributorOptions(): array
    {
        $cached = $this->get('crm.distributor_options');
        
        if ($cached) {
            return $cached;
        }
        
        $defaults = [
            'broadline' => 'Broadline Distributor',
            'specialty' => 'Specialty Distributor',
            'direct' => 'Direct from Manufacturer',
            'local' => 'Local Distributor',
            'regional' => 'Regional Distributor',
            'national' => 'National Chain',
            'other' => 'Other'
        ];
        
        $this->set('crm.distributor_options', $defaults, 'json');
        
        return $defaults;
    }

    /**
     * Get generic CRM options by type
     */
    public function getCrmOptions(string $type): array
    {
        return match ($type) {
            'priority_levels' => $this->getPriorityLevels(),
            'sales_stages' => $this->getSalesStages(),
            'contact_roles' => $this->getContactRoles(),
            'interaction_types' => $this->getInteractionTypes(),
            'market_segments' => $this->getMarketSegments(),
            'distributor_options' => $this->getDistributorOptions(),
            default => throw new InvalidArgumentException("Unknown CRM option type: {$type}")
        };
    }

    // Private helper methods

    /**
     * Detect value type for automatic casting
     */
    private function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) => 'color',
            default => 'string',
        };
    }

    /**
     * Prepare value for storage
     */
    private function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Invalidate related caches
     */
    private function invalidateCache(string $key, ?string $category = null): void
    {
        $this->cache->forget("setting:{$key}");
        
        if ($category) {
            $this->cache->forget("settings:category:{$category}");
        }
        
        $this->cache->forget('settings:public');
        $this->cache->forget('settings:all');
    }
}