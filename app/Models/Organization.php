<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, HasUuids;

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'organizationId');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'organizationId');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'organizationId');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'organizationId');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'organizationId');
    }
}
