<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'title',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Get the contact's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name,
        );
    }
}
