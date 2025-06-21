<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'contact_id',
        'user_id',
        'title',
        'description',
        'value',
        'stage',
        'status',
        'expectedCloseDate',
        'isActive',
    ];

    protected function casts(): array 
    {
        return [
            'expectedCloseDate' => 'datetime',
            'isActive' => 'boolean',
            'value' => 'decimal:2',
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

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'opportunity_id');
    }

    public function scopeActive($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActiveOpportunities($query)
    {
        return $query->where('isActive', true)->whereIn('status', ['open', 'in_progress']);
    }

    public function getStageLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->stage));
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getValueFormattedAttribute(): string
    {
        return $this->value ? '$' . number_format($this->value, 2) : '$0.00';
    }

    public function getExpectedCloseDateFormattedAttribute(): string
    {
        return $this->expectedCloseDate ? $this->expectedCloseDate->format('M j, Y') : 'Not set';
    }
}
