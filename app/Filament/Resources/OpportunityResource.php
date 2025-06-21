<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers;
use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('contact_id')
                    ->relationship('contact', 'firstName')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->firstName} {$record->lastName}"),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),
                Forms\Components\Select::make('stage')
                    ->options([
                        'PROSPECT' => 'Prospect',
                        'QUALIFICATION' => 'Qualification',
                        'PROPOSAL' => 'Proposal',
                        'NEGOTIATION' => 'Negotiation',
                        'WON' => 'Won',
                        'LOST' => 'Lost',
                    ])
                    ->default('PROSPECT')
                    ->required(),
                Forms\Components\TextInput::make('probability')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(50),
                Forms\Components\DateTimePicker::make('expectedCloseDate'),
                Forms\Components\Textarea::make('notes')
                    ->rows(3),
                Forms\Components\TextInput::make('reason')
                    ->maxLength(255),
                Forms\Components\Toggle::make('isActive')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.firstName')
                    ->formatStateUsing(fn ($record) => $record->contact ? "{$record->contact->firstName} {$record->contact->lastName}" : '-')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PROSPECT' => 'gray',
                        'QUALIFICATION' => 'info',
                        'PROPOSAL' => 'warning',
                        'NEGOTIATION' => 'success',
                        'WON' => 'success',
                        'LOST' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('probability')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expectedCloseDate')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('isActive')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContractsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
