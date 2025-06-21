<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
                Forms\Components\Select::make('opportunity_id')
                    ->relationship('opportunity', 'name')
                    ->searchable(),
                Forms\Components\Select::make('contact_id')
                    ->relationship('contact', 'firstName')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->firstName} {$record->lastName}"),
                Forms\Components\Textarea::make('details')
                    ->rows(4),
                Forms\Components\DateTimePicker::make('start_date'),
                Forms\Components\DateTimePicker::make('end_date'),
                Forms\Components\Select::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'ACTIVE' => 'Active',
                        'EXPIRED' => 'Expired',
                        'TERMINATED' => 'Terminated',
                        'COMPLETED' => 'Completed',
                    ])
                    ->default('DRAFT')
                    ->required(),
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
                Tables\Columns\TextColumn::make('opportunity.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.firstName')
                    ->formatStateUsing(fn ($record) => $record->contact ? "{$record->contact->firstName} {$record->contact->lastName}" : '-')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'ACTIVE' => 'success',
                        'EXPIRED' => 'warning',
                        'TERMINATED' => 'danger',
                        'COMPLETED' => 'info',
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
