<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core organization details and classification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Organization Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->unique(ignoreRecord: true),
                                    
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'PROSPECT' => 'Prospect',
                                        'CLIENT' => 'Client',
                                        'DISTRIBUTOR' => 'Distributor',
                                        'SUPPLIER' => 'Supplier',
                                    ])
                                    ->default('PROSPECT')
                                    ->required()
                                    ->live(),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'A' => 'A - Highest Priority',
                                        'B' => 'B - High Priority',
                                        'C' => 'C - Medium Priority',
                                        'D' => 'D - Low Priority',
                                    ])
                                    ->required()
                                    ->default('C')
                                    ->helperText('Priority classification for sales focus'),
                                    
                                Forms\Components\TextInput::make('segment')
                                    ->label('Market Segment')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Fine Dining, Quick Service')
                                    ->helperText('Industry segment or category'),
                                    
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ACTIVE' => 'Active',
                                        'INACTIVE' => 'Inactive', 
                                        'PROSPECT' => 'Prospect',
                                    ])
                                    ->default('ACTIVE')
                                    ->required(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Address Information')
                    ->description('Physical location and contact address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Street Address')
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255)
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('state')
                                    ->maxLength(255)
                                    ->required()
                                    ->placeholder('State/Province'),
                                    
                                Forms\Components\TextInput::make('zipCode')
                                    ->label('ZIP/Postal Code')
                                    ->maxLength(20)
                                    ->rules(['regex:/^[0-9]{5}(-[0-9]{4})?$/']),
                            ]),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Contact Information')
                    ->description('Phone, email, and web presence')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->rules(['regex:/^[\+]?[1-9][\d]{0,15}$/'])
                                    ->helperText('Include country code if international'),
                                    
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                            ]),
                            
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->prefix('https://')
                            ->placeholder('www.company.com')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Business Details')
                    ->description('Business metrics and operational information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimatedRevenue')
                                    ->label('Estimated Annual Revenue')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(1000)
                                    ->minValue(0)
                                    ->helperText('Estimated annual revenue in USD'),
                                    
                                Forms\Components\TextInput::make('employeeCount')
                                    ->label('Number of Employees')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50000)
                                    ->helperText('Approximate employee count'),
                            ]),
                            
                        Forms\Components\TextInput::make('primaryContact')
                            ->label('Primary Contact Person')
                            ->maxLength(255)
                            ->placeholder('Full name of main contact')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Activity Tracking')
                    ->description('Sales activity and follow-up scheduling')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('lastContactDate')
                                    ->label('Last Contact Date')
                                    ->maxDate(now())
                                    ->displayFormat('M j, Y g:i A'),
                                    
                                Forms\Components\DateTimePicker::make('nextFollowUpDate')
                                    ->label('Next Follow-up Date')
                                    ->minDate(now())
                                    ->displayFormat('M j, Y g:i A'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Additional Notes')
                    ->description('Internal notes and observations')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Add any relevant notes about this organization...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Organization Name')
                    ->searchable(['name', 'email', 'phone'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Organization $record): string => 
                        $record->primaryContact ? "Contact: {$record->primaryContact}" : ''
                    ),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'danger',
                        'B' => 'warning', 
                        'C' => 'info',
                        'D' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'A' => 'A - Highest',
                        'B' => 'B - High',
                        'C' => 'C - Medium', 
                        'D' => 'D - Low',
                    }),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'CLIENT' => 'success',
                        'PROSPECT' => 'warning',
                        'DISTRIBUTOR' => 'info',
                        'SUPPLIER' => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->state(fn (Organization $record): string => 
                        trim("{$record->city}, {$record->state}")
                    )
                    ->searchable(['city', 'state'])
                    ->sortable(['city', 'state'])
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('segment')
                    ->label('Market Segment')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('estimatedRevenue')
                    ->label('Est. Revenue')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Phone number copied'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Email copied'),
                    
                Tables\Columns\TextColumn::make('website')
                    ->searchable()
                    ->url(fn ($state) => $state ? (str_starts_with($state, 'http') ? $state : "https://{$state}") : null)
                    ->openUrlInNewTab()
                    ->toggleable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'INACTIVE' => 'gray', 
                        'PROSPECT' => 'warning',
                    }),
                    
                Tables\Columns\TextColumn::make('lastContactDate')
                    ->label('Last Contact')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state->diffInDays(now()) > 30 ? 'warning' : null),
                    
                Tables\Columns\TextColumn::make('nextFollowUpDate')
                    ->label('Follow-up')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->isToday() ? 'warning' : null)),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'A' => 'A - Highest',
                        'B' => 'B - High', 
                        'C' => 'C - Medium',
                        'D' => 'D - Low',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PROSPECT' => 'Prospect',
                        'CLIENT' => 'Client',
                        'DISTRIBUTOR' => 'Distributor',
                        'SUPPLIER' => 'Supplier',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'INACTIVE' => 'Inactive',
                        'PROSPECT' => 'Prospect',
                    ]),
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
                    Tables\Actions\BulkAction::make('changePriority')
                        ->label('Change Priority')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('priority')
                                ->label('New Priority Level')
                                ->options([
                                    'A' => 'A - Highest Priority',
                                    'B' => 'B - High Priority',
                                    'C' => 'C - Medium Priority',
                                    'D' => 'D - Low Priority',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['priority' => $data['priority']]);
                                $count++;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Priority Updated')
                                ->body("Updated priority for {$count} organizations to {$data['priority']}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-m-check-circle')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'ACTIVE' => 'Active',
                                    'INACTIVE' => 'Inactive',
                                    'PROSPECT' => 'Prospect',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                                $count++;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->body("Updated status for {$count} organizations to {$data['status']}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\InteractionsRelationManager::class,
            RelationManagers\OpportunitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
