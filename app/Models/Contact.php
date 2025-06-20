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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'contactId');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'contactId');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contactId');
    }
}
