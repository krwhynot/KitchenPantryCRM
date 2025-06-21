<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'firstName',
        'lastName',
        'email',
        'phone',
        'position',
        'isPrimary',
        'notes',
    ];

    protected function casts(): array 
    {
        return [
            'isPrimary' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'contact_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'contact_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contact_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('isPrimary', true);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeWithEmail($query)
    {
        return $query->whereNotNull('email');
    }

    public function scopeWithPhone($query)
    {
        return $query->whereNotNull('phone');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        if ($this->position) {
            $name .= ' (' . $this->position . ')';
        }
        return $name;
    }

    public function getPrimaryStatusAttribute(): string
    {
        return $this->isPrimary ? 'Primary Contact' : 'Secondary Contact';
    }

    public function getContactInfoAttribute(): string
    {
        $info = [];
        if ($this->email) {
            $info[] = $this->email;
        }
        if ($this->phone) {
            $info[] = $this->phone;
        }
        return implode(' | ', $info) ?: 'No contact info';
    }
}
