<?php

namespace App\Filament\Resources;

use App\Filament\Actions\ContactExportAction;
use App\Filament\Actions\ContactImportAction;
use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'CRM Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core contact details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('firstName')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter first name'),
                                Forms\Components\TextInput::make('lastName')
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter last name'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('contact@example.com')
                                    ->unique(Contact::class, 'email', ignoreRecord: true),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255)
                                    ->placeholder('+1 (555) 123-4567'),
                            ]),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Organization Details')
                    ->description('Organization and professional information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('organization_id')
                                    ->label('Organization')
                                    ->relationship('organization', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->placeholder('Select an organization'),
                                Forms\Components\TextInput::make('position')
                                    ->label('Job Title/Position')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Manager, Director'),
                            ]),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Contact Status')
                    ->description('Contact preferences and status')
                    ->schema([
                        Forms\Components\Toggle::make('isPrimary')
                            ->label('Primary Contact')
                            ->helperText('Designate as the main contact for this organization')
                            ->inline(false),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Additional Information')
                    ->description('Notes and additional details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->placeholder('Add any relevant notes about this contact...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Contact Name')
                    ->description(fn (Contact $record): string => $record->position ?? 'No position specified')
                    ->searchable(['firstName', 'lastName'])
                    ->sortable(['firstName', 'lastName'])
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Contact $record): string => $record->organization ? route('filament.admin.resources.organizations.edit', $record->organization) : '')
                    ->color('primary')
                    ->weight(FontWeight::Medium),
                    
                Tables\Columns\BadgeColumn::make('isPrimary')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Primary' : 'Secondary')
                    ->colors([
                        'success' => fn ($state): bool => $state === true,
                        'gray' => fn ($state): bool => $state === false,
                    ]),
                    
                Tables\Columns\TextColumn::make('contact_info')
                    ->label('Contact Information')
                    ->description(fn (Contact $record): string => 
                        collect([$record->email, $record->phone])
                            ->filter()
                            ->join(' • ') ?: 'No contact info'
                    )
                    ->searchable(['email', 'phone'])
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                
                SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                    
                Filter::make('primary_contacts')
                    ->label('Primary Contacts Only')
                    ->query(fn (Builder $query): Builder => $query->where('isPrimary', true))
                    ->toggle(),
                    
                Filter::make('has_email')
                    ->label('Has Email')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email'))
                    ->toggle(),
                    
                Filter::make('has_phone')
                    ->label('Has Phone')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('phone'))
                    ->toggle(),
                    
                Filter::make('recent')
                    ->label('Added in Last 30 Days')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Change Organization Bulk Action
                    Tables\Actions\BulkAction::make('change_organization')
                        ->label('Change Organization')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Select::make('organization_id')
                                ->label('New Organization')
                                ->options(Organization::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['organization_id' => $data['organization_id']]);
                                $count++;
                            }
                            
                            Notification::make()
                                ->title('Organization Updated')
                                ->body("Successfully updated organization for {$count} contacts.")
                                ->success()
                                ->send();
                        }),
                        
                    // Toggle Primary Status Bulk Action
                    Tables\Actions\BulkAction::make('toggle_primary')
                        ->label('Toggle Primary Status')
                        ->icon('heroicon-o-star')
                        ->form([
                            Forms\Components\Radio::make('is_primary')
                                ->label('Primary Contact Status')
                                ->options([
                                    true => 'Set as Primary',
                                    false => 'Set as Secondary',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['isPrimary' => $data['is_primary']]);
                                $count++;
                            }
                            
                            $status = $data['is_primary'] ? 'primary' : 'secondary';
                            Notification::make()
                                ->title('Primary Status Updated')
                                ->body("Successfully set {$count} contacts as {$status}.")
                                ->success()
                                ->send();
                        }),
                        
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ContactImportAction::make(),
                ContactExportAction::make(),
            ])
            ->defaultSort('firstName')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InteractionsRelationManager::class,
            RelationManagers\OpportunitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
