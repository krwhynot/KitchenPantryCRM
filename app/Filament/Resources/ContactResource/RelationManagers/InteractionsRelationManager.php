<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Models\Interaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Interaction Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->label('Subject')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Meeting subject or interaction topic'),
                                    
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'phone' => 'Phone Call',
                                        'email' => 'Email',
                                        'meeting' => 'Meeting',
                                        'demo' => 'Product Demo',
                                        'follow_up' => 'Follow Up',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->default('meeting'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('interactionDate')
                                    ->label('Date & Time')
                                    ->required()
                                    ->default(now()),
                                    
                                Forms\Components\Select::make('outcome')
                                    ->label('Outcome')
                                    ->options([
                                        'positive' => 'Positive',
                                        'neutral' => 'Neutral',
                                        'negative' => 'Negative',
                                        'follow_up_required' => 'Follow-up Required',
                                        'completed' => 'Completed',
                                    ])
                                    ->required(),
                            ]),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->placeholder('Add details about this interaction...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->description(fn (Interaction $record): string => $record->notes ? \Str::limit($record->notes, 50) : 'No notes')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'phone' => 'Phone Call',
                        'email' => 'Email',
                        'meeting' => 'Meeting',
                        'demo' => 'Demo',
                        'follow_up' => 'Follow Up',
                        'other' => 'Other',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'primary' => 'meeting',
                        'success' => 'phone',
                        'warning' => 'email',
                        'info' => 'demo',
                        'gray' => ['follow_up', 'other'],
                    ]),
                    
                Tables\Columns\TextColumn::make('interactionDate')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn (Interaction $record): string => $record->interactionDate->diffForHumans()),
                    
                Tables\Columns\BadgeColumn::make('outcome')
                    ->label('Outcome')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'positive' => 'Positive',
                        'neutral' => 'Neutral',
                        'negative' => 'Negative',
                        'follow_up_required' => 'Follow-up Required',
                        'completed' => 'Completed',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->colors([
                        'success' => 'positive',
                        'gray' => 'neutral',
                        'danger' => 'negative',
                        'warning' => 'follow_up_required',
                        'info' => 'completed',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'phone' => 'Phone Call',
                        'email' => 'Email',
                        'meeting' => 'Meeting',
                        'demo' => 'Product Demo',
                        'follow_up' => 'Follow Up',
                        'other' => 'Other',
                    ]),
                    
                SelectFilter::make('outcome')
                    ->options([
                        'positive' => 'Positive',
                        'neutral' => 'Neutral',
                        'negative' => 'Negative',
                        'follow_up_required' => 'Follow-up Required',
                        'completed' => 'Completed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['contact_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('interactionDate', 'desc')
            ->emptyStateDescription('No interactions recorded yet. Create one to get started.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}
