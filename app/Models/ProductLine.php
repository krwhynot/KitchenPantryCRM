<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'principal_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPrincipal($query, $principalId)
    {
        return $query->where('principal_id', $principalId);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}
