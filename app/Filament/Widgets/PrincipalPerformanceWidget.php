<?php

namespace App\Filament\Widgets;

use App\Models\Principal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class PrincipalPerformanceWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Principal & Brand Performance';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Performance metrics for food service principals and their product lines';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Principal Name')
                    ->searchable()
                    ->weight('semibold')
                    ->icon('heroicon-o-building-office-2'),

                Tables\Columns\TextColumn::make('product_lines_count')
                    ->label('Product Lines')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Contact Email')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email address copied')
                    ->placeholder('No email'),

                Tables\Columns\TextColumn::make('website')
                    ->label('Website')
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-globe-alt')
                    ->limit(30)
                    ->placeholder('No website'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Primary Contact')
                    ->searchable()
                    ->placeholder('Not assigned'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_contact')
                    ->label('Has Primary Contact')
                    ->query(fn ($query) => $query->whereNotNull('contact_name')),
                    
                Tables\Filters\Filter::make('has_email')
                    ->label('Has Email')
                    ->query(fn ($query) => $query->whereNotNull('email')),
                    
                Tables\Filters\Filter::make('has_website')
                    ->label('Has Website')
                    ->query(fn ($query) => $query->whereNotNull('website')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Principal $record): string => 
                        route('filament.admin.resources.principals.view', ['record' => $record->id])
                    ),
            ])
            ->defaultSort('product_lines_count', 'desc')
            ->paginated([10, 25]);
    }

    public function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        return Principal::withCount('productLines')
            ->orderBy('product_lines_count', 'desc');
    }

    private function getFilterData(): array
    {
        return [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
        ];
    }
}