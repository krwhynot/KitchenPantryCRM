<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityStageHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'opportunity_id',
        'from_stage',
        'to_stage',
        'probability_change',
        'user_id',
        'notes',
    ];

    protected function casts(): array 
    {
        return [
            'probability_change' => 'decimal:2',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedChangeAttribute(): string
    {
        $from = ucwords(str_replace('_', ' ', $this->from_stage));
        $to = ucwords(str_replace('_', ' ', $this->to_stage));
        return "{$from} → {$to}";
    }

    public function getProbabilityChangeFormattedAttribute(): string
    {
        $change = $this->probability_change;
        if ($change > 0) {
            return "+{$change}%";
        } elseif ($change < 0) {
            return "{$change}%";
        }
        return "No change";
    }
}
