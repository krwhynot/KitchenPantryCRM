<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractionResource\Pages;
use App\Filament\Resources\InteractionResource\RelationManagers;
use App\Models\Interaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('contact_id')
                    ->relationship('contact', 'firstName')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->firstName} {$record->lastName}"),
                Forms\Components\Select::make('type')
                    ->options([
                        'CALL' => 'Call',
                        'EMAIL' => 'Email',
                        'MEETING' => 'Meeting',
                        'VISIT' => 'Visit',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3),
                Forms\Components\DateTimePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('duration')
                    ->numeric()
                    ->suffix('minutes')
                    ->minValue(1),
                Forms\Components\Select::make('outcome')
                    ->options([
                        'POSITIVE' => 'Positive',
                        'NEUTRAL' => 'Neutral',
                        'NEGATIVE' => 'Negative',
                        'FOLLOWUPNEEDED' => 'Follow-up Needed',
                    ]),
                Forms\Components\TextInput::make('nextAction')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CALL' => 'info',
                        'EMAIL' => 'warning',
                        'MEETING' => 'success',
                        'VISIT' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.firstName')
                    ->formatStateUsing(fn ($record) => $record->contact ? "{$record->contact->firstName} {$record->contact->lastName}" : '-')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outcome')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'POSITIVE' => 'success',
                        'NEUTRAL' => 'info',
                        'NEGATIVE' => 'danger',
                        'FOLLOWUPNEEDED' => 'warning',
                        default => 'gray',
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
            'index' => Pages\ListInteractions::route('/'),
            'create' => Pages\CreateInteraction::route('/create'),
            'edit' => Pages\EditInteraction::route('/{record}/edit'),
        ];
    }
}
