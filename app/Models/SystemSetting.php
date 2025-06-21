<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
// use Spatie\Activitylog\Traits\LogsActivity;
// use Spatie\Activitylog\LogOptions;

class SystemSetting extends Model
{
    use HasFactory, HasUuids; // LogsActivity;

    protected $fillable = [
        'key', 'value', 'category', 'type', 'description', 
        'default_value', 'validation_rules', 'ui_component', 
        'is_public', 'sort_order'
    ];

    // Laravel 12 optimized casting
    protected $casts = [
        'validation_rules' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Validated type constants (application-level validation)
    const ALLOWED_TYPES = [
        'string' => 'Text',
        'integer' => 'Number', 
        'boolean' => 'Yes/No',
        'json' => 'JSON Object',
        'array' => 'Array/List',
        'color' => 'Color',
        'select' => 'Select Options',
    ];

    const ALLOWED_CATEGORIES = [
        'system' => 'System',
        'crm' => 'CRM',
        'notification' => 'Notifications',
        'user' => 'User Preferences',
        'integration' => 'Integrations',
        'security' => 'Security',
    ];

    /**
     * Performance-optimized typed value accessor
     */
    public function getTypedValueAttribute(): mixed
    {
        return $this->castValue($this->value, $this->type);
    }

    /**
     * Get default typed value
     */
    public function getTypedDefaultValueAttribute(): mixed
    {
        return $this->castValue($this->default_value, $this->type);
    }

    /**
     * Efficient value casting with Laravel 12 patterns
     */
    public function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) return null;

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            'color' => $value, // Validate hex format in application
            default => (string) $value,
        };
    }

    /**
     * Performance-optimized scopes with indexes
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category); // Uses idx_settings_category
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type); // Uses idx_settings_type
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true); // Uses idx_settings_public
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('key'); // Uses idx_settings_category_sort
    }

    /**
     * Cached settings retrieval (performance optimization)
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) return $default;
            
            return $setting->typed_value ?? $setting->typed_default_value ?? $default;
        });
    }

    /**
     * Cache-aware setting updates
     */
    public static function setValue(string $key, mixed $value, ?string $type = null): self
    {
        $type = $type ?? static::detectType($value);
        
        // Validate type is allowed
        if (!array_key_exists($type, static::ALLOWED_TYPES)) {
            throw new \InvalidArgumentException("Invalid setting type: {$type}");
        }
        
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => static::prepareValue($value, $type),
                'type' => $type
            ]
        );

        // Clear cache
        Cache::forget("setting:{$key}");
        Cache::forget("settings:category:{$setting->category}");
        Cache::forget("settings:all");

        return $setting;
    }

    /**
     * Cached category retrieval
     */
    public static function getByCategory(string $category): Collection
    {
        $cacheKey = "settings:category:{$category}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            return static::byCategory($category)
                ->ordered()
                ->get()
                ->mapWithKeys(fn($setting) => [$setting->key => $setting->typed_value]);
        });
    }

    /**
     * Type detection for automatic casting
     */
    protected static function detectType(mixed $value): string
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
     * Value preparation for storage
     */
    protected static function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Application-level validation (replaces enum constraint)
     */
    public function validateType(): bool
    {
        return array_key_exists($this->type, static::ALLOWED_TYPES);
    }

    public function validateCategory(): bool
    {
        return array_key_exists($this->category, static::ALLOWED_CATEGORIES);
    }

    /**
     * Get display value for admin interface
     */
    public function getDisplayValueAttribute(): string
    {
        $value = $this->typed_value;
        
        return match ($this->type) {
            'boolean' => $value ? 'Yes' : 'No',
            'json', 'array' => 'JSON Data (' . count($value ?? []) . ' items)',
            'color' => $value . ' ●',
            default => strlen((string)$value) > 50 
                ? substr((string)$value, 0, 50) . '...' 
                : (string)$value,
        };
    }

    /**
     * Validate setting value against rules
     */
    public function validateValue(mixed $value): bool
    {
        if (!$this->validation_rules) {
            return true;
        }

        $validator = validator(['value' => $value], ['value' => $this->validation_rules]);
        
        return !$validator->fails();
    }

    /**
     * Get available setting types
     */
    public static function getAvailableTypes(): array
    {
        return static::ALLOWED_TYPES;
    }

    /**
     * Get available categories
     */
    public static function getAvailableCategories(): array
    {
        return static::ALLOWED_CATEGORIES;
    }

    /**
     * Configure activity logging options (temporarily disabled)
     */
    /*
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'category', 'type', 'description', 'is_public'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('settings')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Setting "{subject.key}" was created',
                'updated' => 'Setting "{subject.key}" was updated',
                'deleted' => 'Setting "{subject.key}" was deleted',
                default => "Setting \"{subject.key}\" was {$eventName}",
            });
    }
    */

    /**
     * Cache invalidation on model events
     */
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget("setting:{$setting->key}");
            Cache::forget("settings:category:{$setting->category}");
            Cache::forget("settings:all");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting:{$setting->key}");
            Cache::forget("settings:category:{$setting->category}");
            Cache::forget("settings:all");
        });
    }
}
