<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Principal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'website',
        'notes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(ProductLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('name', '!=', '');
    }

    public function getContactDisplayAttribute(): string
    {
        return $this->contact_name ?: 'No contact assigned';
    }
}
