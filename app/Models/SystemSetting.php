<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function getDisplayValueAttribute(): string
    {
        if (is_json($this->value)) {
            return 'JSON Data';
        }
        
        return strlen($this->value) > 50 
            ? substr($this->value, 0, 50) . '...' 
            : $this->value;
    }
}
