<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

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
}
