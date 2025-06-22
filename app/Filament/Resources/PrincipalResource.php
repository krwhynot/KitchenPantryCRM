<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrincipalResource\Pages;
use App\Filament\Resources\PrincipalResource\RelationManagers;
use App\Models\Principal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrincipalResource extends Resource
{
    protected static ?string $model = Principal::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Brand Management';
    
    protected static ?string $navigationLabel = 'Principals';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Principal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Principal Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreDuringUpdate: true)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Primary Contact')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreDuringUpdate: true),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('address')
                            ->maxLength(500)
                            ->rows(2),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Principal Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Primary Contact')
                    ->searchable()
                    ->placeholder('Not assigned'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                    
                Tables\Columns\TextColumn::make('product_lines_count')
                    ->label('Product Lines')
                    ->counts('productLines')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('website')
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-globe-alt')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_contact')
                    ->label('Has Primary Contact')
                    ->query(fn (Builder $query) => $query->whereNotNull('contact_name')),
                    
                Tables\Filters\Filter::make('has_email')
                    ->label('Has Email')
                    ->query(fn (Builder $query) => $query->whereNotNull('email')),
                    
                Tables\Filters\Filter::make('has_product_lines')
                    ->label('Has Product Lines')
                    ->query(fn (Builder $query) => $query->has('productLines')),
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
            ->defaultSort('name')
            ->emptyStateHeading('No principals found')
            ->emptyStateDescription('Create your first principal to start managing brand relationships.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrincipals::route('/'),
            'create' => Pages\CreatePrincipal::route('/create'),
            'edit' => Pages\EditPrincipal::route('/{record}/edit'),
        ];
    }
}
