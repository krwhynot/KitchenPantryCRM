<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'contact_id',
        'user_id',
        'type',
        'date',
        'notes',
    ];

    protected function casts(): array 
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date', 'desc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getDateFormattedAttribute(): string
    {
        return $this->date ? $this->date->format('M j, Y g:i A') : '';
    }
}
