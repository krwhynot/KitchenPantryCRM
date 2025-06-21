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
        'subject',
        'notes',
        'interactionDate',
        'duration',
        'outcome',
        'priority',
        'nextAction',
        'follow_up_date',
    ];

    protected function casts(): array 
    {
        return [
            'interactionDate' => 'datetime',
            'follow_up_date' => 'date',
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
        return $query->orderBy('interactionDate', 'desc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeRequiresFollowUp($query)
    {
        return $query->where('outcome', 'FOLLOWUPNEEDED')
                    ->orWhereNotNull('follow_up_date');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'CALL' => 'Phone Call',
            'EMAIL' => 'Email',
            'MEETING' => 'Meeting',
            'VISIT' => 'Site Visit',
            default => ucfirst(strtolower($this->type)),
        };
    }

    public function getOutcomeLabelAttribute(): string
    {
        return match($this->outcome) {
            'POSITIVE' => 'Positive',
            'NEUTRAL' => 'Neutral',
            'NEGATIVE' => 'Negative',
            'FOLLOWUPNEEDED' => 'Follow-up Needed',
            default => 'Not Set',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority);
    }

    public function getDateFormattedAttribute(): string
    {
        return $this->interactionDate ? $this->interactionDate->format('M j, Y g:i A') : '';
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return 'Not specified';
        
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . ' minutes';
    }

    public function getContactDisplayNameAttribute(): string
    {
        return $this->contact ? $this->contact->full_name : 'No contact specified';
    }
}
