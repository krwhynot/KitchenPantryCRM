<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'priority',
        'segment',
        'type',
        'address',
        'city',
        'state',
        'zipCode',
        'phone',
        'email',
        'website',
        'notes',
        'estimatedRevenue',
        'employeeCount',
        'primaryContact',
        'lastContactDate',
        'nextFollowUpDate',
        'status',
    ];

    protected function casts(): array 
    {
        return [
            'lastContactDate' => 'datetime',
            'nextFollowUpDate' => 'datetime',
            'estimatedRevenue' => 'decimal:2',
            'employeeCount' => 'integer',
        ];
    }

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

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeBySegment($query, $segment)
    {
        return $query->where('segment', $segment);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'A' => 'High Priority',
            'B' => 'Medium Priority',
            'C' => 'Low Priority',
            'D' => 'Lowest Priority',
        ];
        
        return $labels[$this->priority] ?? 'Unknown';
    }

    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', strtolower($this->type)));
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zipCode,
        ]);
        
        return implode(', ', $parts);
    }

    public function getEstimatedRevenueFormattedAttribute(): string
    {
        return $this->estimatedRevenue ? '$' . number_format($this->estimatedRevenue, 2) : 'Not specified';
    }

    public function getLastContactDateFormattedAttribute(): string
    {
        return $this->lastContactDate ? $this->lastContactDate->format('M j, Y') : 'Never contacted';
    }

    public function getNextFollowUpDateFormattedAttribute(): string
    {
        return $this->nextFollowUpDate ? $this->nextFollowUpDate->format('M j, Y') : 'Not scheduled';
    }
}
