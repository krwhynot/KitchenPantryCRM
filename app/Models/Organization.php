<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'organization_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'organization_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'organization_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'organization_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'organization_id');
    }
}
