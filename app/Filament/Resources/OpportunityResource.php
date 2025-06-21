<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Sales Pipeline';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Opportunity Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter opportunity title'),
                                    
                                Forms\Components\Select::make('organization_id')
                                    ->relationship('organization', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $set('contact_id', null);
                                        }
                                    }),
                                    
                                Forms\Components\Select::make('contact_id')
                                    ->options(function (Get $get): array {
                                        $organizationId = $get('organization_id');
                                        if (!$organizationId) {
                                            return [];
                                        }
                                        return Contact::where('organization_id', $organizationId)
                                            ->get()
                                            ->pluck('full_name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->placeholder('Select contact...'),
                                    
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->default(auth()->id())
                                    ->searchable(),
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Describe the opportunity...')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Sales Pipeline')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('stage')
                                    ->options(Opportunity::getStageOptions())
                                    ->required()
                                    ->default('lead')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $stageProbabilities = [
                                            'lead' => 10,
                                            'prospect' => 25,
                                            'proposal' => 50,
                                            'negotiation' => 75,
                                            'closed' => 100
                                        ];
                                        $set('probability', $stageProbabilities[$state] ?? 10);
                                    }),
                                    
                                Forms\Components\TextInput::make('probability')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(10),
                                    
                                Forms\Components\Select::make('status')
                                    ->options(Opportunity::getStatusOptions())
                                    ->required()
                                    ->default('open'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00'),
                                    
                                Forms\Components\DatePicker::make('expectedCloseDate')
                                    ->label('Expected Close Date')
                                    ->required(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('priority')
                                    ->options(Opportunity::getPriorityOptions())
                                    ->required()
                                    ->default('medium'),
                                    
                                Forms\Components\TextInput::make('source')
                                    ->placeholder('Lead source'),
                                    
                                Forms\Components\TextInput::make('lead_score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('pts'),
                            ]),
                            
                        Forms\Components\Textarea::make('next_action')
                            ->rows(2)
                            ->placeholder('What is the next action to take?')
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->placeholder('Additional notes...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Hidden fields for tracking
                Forms\Components\Hidden::make('stage_changed_at')
                    ->default(now()),
                Forms\Components\Hidden::make('stage_changed_by_user_id')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('last_activity_date')
                    ->default(now()),
                Forms\Components\Hidden::make('isActive')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Opportunity')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Contact')
                    ->searchable()
                    ->toggleable(),
                    
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
                    
                Tables\Columns\TextColumn::make('probability')
                    ->suffix('%')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'won',
                        'danger' => 'lost',
                        'primary' => 'open',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords($state)),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'high',
                        'warning' => 'medium',
                        'secondary' => 'low',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords($state)),
                    
                Tables\Columns\TextColumn::make('expectedCloseDate')
                    ->label('Expected Close')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('last_activity_date')
                    ->label('Last Activity')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options(Opportunity::getStageOptions()),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options(Opportunity::getStatusOptions()),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Opportunity::getPriorityOptions()),
                    
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable(),
                    
                Tables\Filters\Filter::make('high_value')
                    ->query(fn (Builder $query): Builder => $query->where('value', '>=', 10000))
                    ->label('High Value (≥$10K)'),
                    
                Tables\Filters\Filter::make('stale')
                    ->query(fn (Builder $query): Builder => $query->stale())
                    ->label('Stale (No activity 30+ days)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('moveStage')
                    ->label('Move Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('new_stage')
                            ->label('Move to Stage')
                            ->options(Opportunity::getStageOptions())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->placeholder('Reason for stage change...'),
                    ])
                    ->action(function (Opportunity $record, array $data) {
                        $record->updateStage($data['new_stage'], $data['notes'] ?? null);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updatePriority')
                        ->label('Update Priority')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Forms\Components\Select::make('priority')
                                ->options(Opportunity::getPriorityOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['priority' => $data['priority']]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
            'kanban' => Pages\KanbanOpportunities::route('/kanban'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
