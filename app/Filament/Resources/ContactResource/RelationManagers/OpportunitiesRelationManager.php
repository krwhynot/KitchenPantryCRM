<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class OpportunitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunities';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Opportunity Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Opportunity Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter opportunity name'),
                                    
                                Forms\Components\TextInput::make('estimatedValue')
                                    ->label('Estimated Value')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('stage')
                                    ->label('Sales Stage')
                                    ->options([
                                        'prospecting' => 'Prospecting',
                                        'qualification' => 'Qualification',
                                        'proposal' => 'Proposal',
                                        'negotiation' => 'Negotiation',
                                        'closed_won' => 'Closed Won',
                                        'closed_lost' => 'Closed Lost',
                                    ])
                                    ->required()
                                    ->default('prospecting'),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('active'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('expectedCloseDate')
                                    ->label('Expected Close Date')
                                    ->default(now()->addDays(30)),
                                    
                                Forms\Components\TextInput::make('probability')
                                    ->label('Probability (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->default(50),
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Describe this opportunity...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Opportunity')
                    ->description(fn (Opportunity $record): string => $record->description ? \Str::limit($record->description, 50) : 'No description')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\BadgeColumn::make('stage')
                    ->label('Stage')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'prospecting' => 'Prospecting',
                        'qualification' => 'Qualification',
                        'proposal' => 'Proposal',
                        'negotiation' => 'Negotiation',
                        'closed_won' => 'Closed Won',
                        'closed_lost' => 'Closed Lost',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'gray' => 'prospecting',
                        'warning' => 'qualification',
                        'info' => 'proposal',
                        'primary' => 'negotiation',
                        'success' => 'closed_won',
                        'danger' => 'closed_lost',
                    ]),
                    
                Tables\Columns\TextColumn::make('estimatedValue')
                    ->label('Value')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('probability')
                    ->label('Probability')
                    ->formatStateUsing(fn (?int $state): string => $state ? $state . '%' : 'N/A')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                    
                Tables\Columns\TextColumn::make('expectedCloseDate')
                    ->label('Expected Close')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn (Opportunity $record): string => 
                        $record->expectedCloseDate ? 
                        $record->expectedCloseDate->diffForHumans() : 
                        'No date set'
                    ),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                
                SelectFilter::make('stage')
                    ->options([
                        'prospecting' => 'Prospecting',
                        'qualification' => 'Qualification',
                        'proposal' => 'Proposal',
                        'negotiation' => 'Negotiation',
                        'closed_won' => 'Closed Won',
                        'closed_lost' => 'Closed Lost',
                    ]),
                    
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['contact_id'] = $this->ownerRecord->id;
                        $data['organization_id'] = $this->ownerRecord->organization_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateDescription('No opportunities found for this contact. Create one to get started.')
            ->emptyStateIcon('heroicon-o-light-bulb')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}