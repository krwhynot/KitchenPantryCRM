<?php

namespace App\Filament\Resources;

use App\Filament\Actions\InteractionExportAction;
use App\Filament\Actions\InteractionImportAction;
use App\Filament\Resources\InteractionResource\Pages;
use App\Filament\Resources\InteractionResource\RelationManagers;
use App\Models\Interaction;
use App\Models\Organization;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationGroup = 'CRM Management';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Essential Information (30-second target)
                Forms\Components\Section::make('Quick Entry')
                    ->description('Essential fields for fast interaction logging')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('organization_id')
                                    ->label('Organization')
                                    ->relationship('organization', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->searchDebounce(300)
                                    ->optionsLimit(20)
                                    ->placeholder('Start typing organization name...')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                    ])
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            // Auto-populate contacts for selected organization
                                            $set('contact_id', null);
                                        }
                                    }),
                                    
                                Forms\Components\Select::make('contact_id')
                                    ->label('Contact')
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
                                    ->preload()
                                    ->placeholder('Select a contact...')
                                    ->searchDebounce(300),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'CALL' => 'Phone Call',
                                        'EMAIL' => 'Email',
                                        'MEETING' => 'Meeting',
                                        'VISIT' => 'Site Visit',
                                    ])
                                    ->required()
                                    ->default('CALL')
                                    ->native(false),
                                    
                                Forms\Components\DateTimePicker::make('interactionDate')
                                    ->label('Date & Time')
                                    ->required()
                                    ->default(now())
                                    ->seconds(false),
                                    
                                Forms\Components\TextInput::make('duration')
                                    ->label('Duration (min)')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->minValue(1)
                                    ->maxValue(480)
                                    ->default(15)
                                    ->placeholder('15'),
                            ]),
                            
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Brief subject line...')
                            ->autofocus()
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Quick Notes')
                            ->rows(3)
                            ->placeholder('Key points from this interaction...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(false),
                    
                // Additional Details (Optional)
                Forms\Components\Section::make('Additional Details')
                    ->description('Optional fields for comprehensive tracking')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('outcome')
                                    ->label('Outcome')
                                    ->options([
                                        'POSITIVE' => 'Positive',
                                        'NEUTRAL' => 'Neutral',
                                        'NEGATIVE' => 'Negative',
                                        'FOLLOWUPNEEDED' => 'Follow-up Needed',
                                    ])
                                    ->placeholder('Select outcome...')
                                    ->native(false),
                                    
                                Forms\Components\Select::make('priority')
                                    ->label('Priority')
                                    ->options([
                                        'low' => 'Low',
                                        'medium' => 'Medium',
                                        'high' => 'High',
                                    ])
                                    ->default('medium')
                                    ->native(false),
                                    
                                Forms\Components\DatePicker::make('follow_up_date')
                                    ->label('Follow-up Date')
                                    ->placeholder('If follow-up needed...')
                                    ->default(function (Get $get) {
                                        return $get('outcome') === 'FOLLOWUPNEEDED' ? now()->addDays(7)->format('Y-m-d') : null;
                                    }),
                            ]),
                            
                        Forms\Components\TextInput::make('nextAction')
                            ->label('Next Action')
                            ->maxLength(255)
                            ->placeholder('What needs to happen next?')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsed()
                    ->collapsible(),
                    
                // System Fields (Hidden for quick entry)
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'CALL' => 'Call',
                        'EMAIL' => 'Email',
                        'MEETING' => 'Meeting',
                        'VISIT' => 'Visit',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'CALL',
                        'warning' => 'EMAIL',
                        'success' => 'MEETING',
                        'danger' => 'VISIT',
                    ]),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->description(fn (Interaction $record): string => 
                        $record->notes ? \Str::limit($record->notes, 60) : 'No notes'
                    )
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Interaction $record): string => 
                        $record->organization ? route('filament.admin.resources.organizations.edit', $record->organization) : ''
                    )
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('contact_display_name')
                    ->label('Contact')
                    ->description(fn (Interaction $record): string => 
                        $record->contact?->position ?? 'No position'
                    )
                    ->url(fn (Interaction $record): string => 
                        $record->contact ? route('filament.admin.resources.contacts.edit', $record->contact) : ''
                    )
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('interactionDate')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn (Interaction $record): string => 
                        $record->interactionDate->diffForHumans()
                    ),
                    
                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('duration', $direction);
                    }),
                    
                Tables\Columns\BadgeColumn::make('outcome')
                    ->label('Outcome')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'POSITIVE' => 'Positive',
                        'NEUTRAL' => 'Neutral',
                        'NEGATIVE' => 'Negative',
                        'FOLLOWUPNEEDED' => 'Follow-up',
                        default => 'Not Set',
                    })
                    ->colors([
                        'success' => 'POSITIVE',
                        'gray' => 'NEUTRAL',
                        'danger' => 'NEGATIVE',
                        'warning' => 'FOLLOWUPNEEDED',
                    ])
                    ->placeholder('Not Set'),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'danger' => 'high',
                        'warning' => 'medium',
                        'success' => 'low',
                    ])
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('M j, Y')
                    ->placeholder('None')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                    
                SelectFilter::make('type')
                    ->options([
                        'CALL' => 'Phone Call',
                        'EMAIL' => 'Email',
                        'MEETING' => 'Meeting',
                        'VISIT' => 'Site Visit',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('outcome')
                    ->options([
                        'POSITIVE' => 'Positive',
                        'NEUTRAL' => 'Neutral',
                        'NEGATIVE' => 'Negative',
                        'FOLLOWUPNEEDED' => 'Follow-up Needed',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ])
                    ->multiple(),
                    
                Filter::make('follow_up_required')
                    ->label('Requires Follow-up')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('outcome', 'FOLLOWUPNEEDED')
                              ->orWhereNotNull('follow_up_date')
                    )
                    ->toggle(),
                    
                Filter::make('recent')
                    ->label('Last 7 Days')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('interactionDate', '>=', now()->subDays(7))
                    )
                    ->toggle(),
                    
                Filter::make('my_interactions')
                    ->label('My Interactions')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('user_id', auth()->id())
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // Quick duplicate action
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Interaction $record) {
                        $newInteraction = $record->replicate();
                        $newInteraction->subject = 'Copy of ' . $record->subject;
                        $newInteraction->interactionDate = now();
                        $newInteraction->user_id = auth()->id();
                        $newInteraction->save();
                        
                        Notification::make()
                            ->title('Interaction Duplicated')
                            ->body('A copy of this interaction has been created.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Bulk change type
                    Tables\Actions\BulkAction::make('change_type')
                        ->label('Change Type')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Select::make('type')
                                ->label('New Type')
                                ->options([
                                    'CALL' => 'Phone Call',
                                    'EMAIL' => 'Email',
                                    'MEETING' => 'Meeting',
                                    'VISIT' => 'Site Visit',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['type' => $data['type']]);
                                $count++;
                            }
                            
                            Notification::make()
                                ->title('Type Updated')
                                ->body("Successfully updated type for {$count} interactions.")
                                ->success()
                                ->send();
                        }),
                        
                    // Bulk change outcome
                    Tables\Actions\BulkAction::make('change_outcome')
                        ->label('Set Outcome')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Select::make('outcome')
                                ->label('Outcome')
                                ->options([
                                    'POSITIVE' => 'Positive',
                                    'NEUTRAL' => 'Neutral',
                                    'NEGATIVE' => 'Negative',
                                    'FOLLOWUPNEEDED' => 'Follow-up Needed',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['outcome' => $data['outcome']]);
                                $count++;
                            }
                            
                            Notification::make()
                                ->title('Outcome Updated')
                                ->body("Successfully updated outcome for {$count} interactions.")
                                ->success()
                                ->send();
                        }),
                        
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                InteractionImportAction::make(),
                InteractionExportAction::make(),
            ])
            ->defaultSort('interactionDate', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListInteractions::route('/'),
            'create' => Pages\CreateInteraction::route('/create'),
            'edit' => Pages\EditInteraction::route('/{record}/edit'),
        ];
    }
}
