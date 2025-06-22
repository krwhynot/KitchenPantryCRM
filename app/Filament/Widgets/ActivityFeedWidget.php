<?php

namespace App\Filament\Widgets;

use App\Models\Interaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class ActivityFeedWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Recent Activity';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Latest interactions and activities across the system';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'info',
                        'phone' => 'success', 
                        'meeting' => 'warning',
                        'note' => 'gray',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->weight('semibold')
                    ->placeholder('N/A'),
                    
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Contact')
                    ->searchable()
                    ->placeholder('N/A'),
                    
                Tables\Columns\TextColumn::make('outcome')
                    ->label('Outcome')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'successful' => 'success',
                        'follow_up_required' => 'warning',
                        'no_response' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? str_replace('_', ' ', ucfirst($state)) : 'N/A'
                    ),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->icon('heroicon-o-user')
                    ->placeholder('System'),
                    
                Tables\Columns\TextColumn::make('interaction_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->wrap()
                    ->placeholder('No notes'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'email' => 'Email',
                        'phone' => 'Phone',
                        'meeting' => 'Meeting',
                        'note' => 'Note',
                    ]),
                    
                Tables\Filters\SelectFilter::make('outcome')
                    ->options([
                        'successful' => 'Successful',
                        'follow_up_required' => 'Follow-up Required',
                        'no_response' => 'No Response',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Interaction $record): string => 
                        route('filament.admin.resources.interactions.view', ['record' => $record->id])
                    ),
            ])
            ->defaultSort('interaction_date', 'desc')
            ->paginated([10, 25])
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        $query = Interaction::with(['organization', 'contact', 'user']);
        
        $filters = $this->getFilterData();
        
        // Apply date filters
        if (!empty($filters['startDate'])) {
            $query->where('interaction_date', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->where('interaction_date', '<=', $filters['endDate']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        return $query->orderBy('interaction_date', 'desc')->limit(25);
    }

    private function getFilterData(): array
    {
        return [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
            'user_id' => $this->pageFilters['user_id'] ?? null,
            'organization_id' => $this->pageFilters['organization_id'] ?? null,
        ];
    }
}