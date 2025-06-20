<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers;
use App\Models\Contact;
use App\Models\Opportunity;
use Filament\Forms\Get;
use Illuminate\Support\Collection;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('contact_id')
                            ->label('Primary Contact')
                            ->options(fn (Get $get): Collection => Contact::query()
                                ->where('organization_id', $get('organization_id'))
                                ->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('stage')
                            ->options([
                                'prospecting' => 'Prospecting',
                                'qualifying' => 'Qualifying',
                                'proposal' => 'Proposal',
                                'negotiation' => 'Negotiation',
                                'closed_won' => 'Closed Won',
                                'closed_lost' => 'Closed Lost',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\DatePicker::make('close_date'),
                    ])->columns(2),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('close_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'prospecting' => 'Prospecting',
                        'qualifying' => 'Qualifying',
                        'proposal' => 'Proposal',
                        'negotiation' => 'Negotiation',
                        'closed_won' => 'Closed Won',
                        'closed_lost' => 'Closed Lost',
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
