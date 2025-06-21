<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Opportunity extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        'probability',
        'lead_score',
        'stage_changed_at',
        'stage_changed_by_user_id',
        'source',
        'priority',
        'next_action',
        'last_activity_date',
    ];

    protected function casts(): array 
    {
        return [
            'expectedCloseDate' => 'datetime',
            'isActive' => 'boolean',
            'value' => 'decimal:2',
            'probability' => 'decimal:2',
            'lead_score' => 'integer',
            'stage_changed_at' => 'datetime',
            'last_activity_date' => 'datetime',
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

    public function stageChangedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stage_changed_by_user_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class);
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

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeHighValue($query, $threshold = 10000)
    {
        return $query->where('value', '>=', $threshold);
    }

    public function scopeByProbability($query, $min = 0, $max = 100)
    {
        return $query->whereBetween('probability', [$min, $max]);
    }

    public function scopeExpectedCloseThisMonth($query)
    {
        return $query->whereBetween('expectedCloseDate', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    public function scopeStale($query, $days = 30)
    {
        return $query->where('last_activity_date', '<', Carbon::now()->subDays($days))
            ->orWhereNull('last_activity_date');
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

    public function getProbabilityFormattedAttribute(): string
    {
        return $this->probability ? $this->probability . '%' : '0%';
    }

    public function getWeightedValueAttribute(): float
    {
        return ($this->value ?? 0) * (($this->probability ?? 0) / 100);
    }

    public function getWeightedValueFormattedAttribute(): string
    {
        return '$' . number_format($this->getWeightedValueAttribute(), 2);
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'high' => 'High Priority',
            'medium' => 'Medium Priority', 
            'low' => 'Low Priority',
            default => 'Not Set'
        };
    }

    public function getDaysInStageAttribute(): int
    {
        if (!$this->stage_changed_at) {
            return $this->created_at->diffInDays(now());
        }
        return $this->stage_changed_at->diffInDays(now());
    }

    public function getIsStaleAttribute(): bool
    {
        if (!$this->last_activity_date) {
            return $this->created_at->diffInDays(now()) > 30;
        }
        return $this->last_activity_date->diffInDays(now()) > 30;
    }

    public function updateStage(string $newStage, ?string $notes = null): bool
    {
        $oldStage = $this->stage;
        $oldProbability = $this->probability;
        
        // Update stage with probability based on stage
        $stageProbabilities = [
            'lead' => 10,
            'prospect' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'closed' => 100
        ];
        
        $this->stage = $newStage;
        $this->probability = $stageProbabilities[$newStage] ?? $this->probability;
        $this->stage_changed_at = now();
        $this->stage_changed_by_user_id = auth()->id();
        $this->last_activity_date = now();
        
        $saved = $this->save();
        
        if ($saved) {
            // Create stage history record
            OpportunityStageHistory::create([
                'opportunity_id' => $this->id,
                'from_stage' => $oldStage,
                'to_stage' => $newStage,
                'probability_change' => $this->probability - $oldProbability,
                'user_id' => auth()->id(),
                'notes' => $notes,
            ]);
        }
        
        return $saved;
    }

    public static function getStageOptions(): array
    {
        return [
            'lead' => 'Lead',
            'prospect' => 'Prospect',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'closed' => 'Closed'
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'open' => 'Open',
            'won' => 'Won',
            'lost' => 'Lost'
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low'
        ];
    }
}
