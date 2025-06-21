<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class AtRiskDealsWidget extends BaseWidget
{
    protected static ?string $heading = 'At-Risk Opportunities';
    
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $now = Carbon::now();
        
        return Opportunity::query()
            ->with(['organization', 'user'])
            ->where('isActive', true)
            ->where('status', 'open')
            ->where(function ($query) use ($now) {
                $query
                    // Deals stale for more than 30 days
                    ->where(function ($q) use ($now) {
                        $q->where('last_activity_date', '<', $now->subDays(30))
                          ->orWhereNull('last_activity_date');
                    })
                    // OR deals past expected close date
                    ->orWhere('expectedCloseDate', '<', $now)
                    // OR deals in negotiation stage for more than 45 days
                    ->orWhere(function ($q) use ($now) {
                        $q->where('stage', 'negotiation')
                          ->where('stage_changed_at', '<', $now->subDays(45));
                    })
                    // OR high-value deals with declining probability
                    ->orWhere(function ($q) {
                        $q->where('value', '>=', 50000)
                          ->where('probability', '<', 25);
                    });
            })
            ->orderByRaw('
                CASE 
                    WHEN expectedCloseDate < NOW() THEN 1
                    WHEN stage = "negotiation" AND stage_changed_at < DATE_SUB(NOW(), INTERVAL 45 DAY) THEN 2
                    WHEN last_activity_date < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_activity_date IS NULL THEN 3
                    WHEN value >= 50000 AND probability < 25 THEN 4
                    ELSE 5
                END
            ')
            ->limit(10);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Opportunity')
                    ->weight('bold')
                    ->limit(25)
                    ->tooltip(function ($record) {
                        return $record->title;
                    }),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('stage')
                    ->colors([
                        'secondary' => 'lead',
                        'warning' => 'prospect',
                        'info' => 'proposal',
                        'primary' => 'negotiation',
                        'success' => 'closed',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                    
                Tables\Columns\TextColumn::make('risk_reason')
                    ->label('Risk Factor')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function ($record) {
                        $now = Carbon::now();
                        
                        if ($record->expectedCloseDate && $record->expectedCloseDate->lt($now)) {
                            return 'Overdue';
                        }
                        
                        if ($record->stage === 'negotiation' && 
                            $record->stage_changed_at && 
                            $record->stage_changed_at->lt($now->subDays(45))) {
                            return 'Stalled';
                        }
                        
                        if (!$record->last_activity_date || 
                            $record->last_activity_date->lt($now->subDays(30))) {
                            return 'No Activity';
                        }
                        
                        if ($record->value >= 50000 && $record->probability < 25) {
                            return 'Low Probability';
                        }
                        
                        return 'At Risk';
                    }),
                    
                Tables\Columns\TextColumn::make('last_activity_date')
                    ->label('Last Activity')
                    ->date()
                    ->color(function ($record) {
                        if (!$record->last_activity_date) {
                            return 'danger';
                        }
                        
                        $daysSince = Carbon::now()->diffInDays($record->last_activity_date);
                        
                        if ($daysSince > 30) return 'danger';
                        if ($daysSince > 14) return 'warning';
                        return 'success';
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->limit(15),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Opportunity $record): string => "/admin/opportunities/{$record->id}/edit")
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('contact')
                    ->label('Contact')
                    ->icon('heroicon-o-phone')
                    ->color('primary')
                    ->action(function (Opportunity $record) {
                        // This would typically open a modal or redirect to create interaction
                        // For now, we'll just show a notification
                        \Filament\Notifications\Notification::make()
                            ->title('Contact Reminder')
                            ->body("Remember to follow up on {$record->title}")
                            ->info()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No At-Risk Deals')
            ->emptyStateDescription('All opportunities are tracking well!')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->striped()
            ->paginated(false);
    }
}