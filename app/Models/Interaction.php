<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Interaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'type',
        'interaction_date',
        'notes',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
