<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Filament\Resources\SystemSettingResource\RelationManagers;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\HtmlString;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'System Settings';
    
    protected static ?string $modelLabel = 'Setting';
    
    protected static ?string $pluralModelLabel = 'Settings';
    
    protected static ?string $navigationGroup = 'Administration';
    
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Configure the basic setting properties')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Setting Key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('app.setting_name')
                                    ->helperText('Unique identifier for this setting')
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->required()
                                    ->options(SystemSetting::ALLOWED_CATEGORIES)
                                    ->searchable()
                                    ->placeholder('Select a category'),
                            ]),
                        
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->placeholder('Brief description of what this setting controls')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Value Configuration')
                    ->description('Configure the setting value and type')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Data Type')
                            ->required()
                            ->options(SystemSetting::ALLOWED_TYPES)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('value', null))
                            ->placeholder('Select data type'),

                        // Dynamic value field based on type
                        Forms\Components\Grid::make(1)
                            ->schema(function (Get $get): array {
                                $type = $get('type');
                                
                                return match ($type) {
                                    'string' => [
                                        Forms\Components\TextInput::make('value')
                                            ->label('String Value')
                                            ->placeholder('Enter text value')
                                            ->maxLength(1000),
                                    ],
                                    
                                    'integer' => [
                                        Forms\Components\TextInput::make('value')
                                            ->label('Integer Value')
                                            ->numeric()
                                            ->placeholder('Enter number')
                                            ->integer(),
                                    ],
                                    
                                    'boolean' => [
                                        Forms\Components\Toggle::make('value')
                                            ->label('Boolean Value')
                                            ->helperText('Enable or disable this setting'),
                                    ],
                                    
                                    'json' => [
                                        Forms\Components\Textarea::make('value')
                                            ->label('JSON Value')
                                            ->placeholder('{"key": "value"}')
                                            ->rows(6)
                                            ->helperText('Enter valid JSON data')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                                if ($state && !json_decode($state)) {
                                                    $set('value', json_encode(json_decode($state) ?: [], JSON_PRETTY_PRINT));
                                                }
                                            }),
                                    ],
                                    
                                    'array' => [
                                        Forms\Components\Repeater::make('value')
                                            ->label('Array Values')
                                            ->schema([
                                                Forms\Components\TextInput::make('item')
                                                    ->label('Value')
                                                    ->required(),
                                            ])
                                            ->addActionLabel('Add Item')
                                            ->defaultItems(1)
                                            ->collapsible(),
                                    ],
                                    
                                    'color' => [
                                        Forms\Components\ColorPicker::make('value')
                                            ->label('Color Value')
                                            ->placeholder('#000000'),
                                    ],
                                    
                                    'select' => [
                                        Forms\Components\TextInput::make('value')
                                            ->label('Selected Value')
                                            ->placeholder('Enter the selected option')
                                            ->helperText('The value from the available options'),
                                    ],
                                    
                                    default => [],
                                };
                            })
                            ->key('dynamicValueField'),

                        // Default value field
                        Forms\Components\Grid::make(1)
                            ->schema(function (Get $get): array {
                                $type = $get('type');
                                
                                if (!$type) return [];
                                
                                return match ($type) {
                                    'string' => [
                                        Forms\Components\TextInput::make('default_value')
                                            ->label('Default String Value')
                                            ->placeholder('Default text value'),
                                    ],
                                    
                                    'integer' => [
                                        Forms\Components\TextInput::make('default_value')
                                            ->label('Default Integer Value')
                                            ->numeric()
                                            ->placeholder('Default number'),
                                    ],
                                    
                                    'boolean' => [
                                        Forms\Components\Toggle::make('default_value')
                                            ->label('Default Boolean Value'),
                                    ],
                                    
                                    'json', 'array' => [
                                        Forms\Components\Textarea::make('default_value')
                                            ->label('Default JSON/Array Value')
                                            ->placeholder('{"default": "value"}')
                                            ->rows(3),
                                    ],
                                    
                                    'color' => [
                                        Forms\Components\ColorPicker::make('default_value')
                                            ->label('Default Color Value'),
                                    ],
                                    
                                    default => [
                                        Forms\Components\TextInput::make('default_value')
                                            ->label('Default Value'),
                                    ],
                                };
                            })
                            ->key('dynamicDefaultField'),
                    ]),

                Forms\Components\Section::make('Advanced Configuration')
                    ->description('Advanced settings for validation and UI')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('ui_component')
                                    ->label('UI Component')
                                    ->options([
                                        'text_input' => 'Text Input',
                                        'number_input' => 'Number Input',
                                        'toggle' => 'Toggle Switch',
                                        'select_dropdown' => 'Select Dropdown',
                                        'textarea' => 'Text Area',
                                        'color_picker' => 'Color Picker',
                                        'json_editor' => 'JSON Editor',
                                        'list_editor' => 'List Editor',
                                    ])
                                    ->placeholder('Auto-detect from type'),
                                
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower numbers appear first')
                                    ->minValue(0),
                            ]),

                        Forms\Components\Textarea::make('validation_rules')
                            ->label('Validation Rules (JSON)')
                            ->placeholder('["required", "string", "max:255"]')
                            ->rows(3)
                            ->helperText('Laravel validation rules as JSON array')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Setting')
                            ->helperText('Public settings are visible to all users')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (SystemSetting $record): string => Str::limit($record->description ?? '', 50)),
                
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'system' => 'gray',
                        'crm' => 'success',
                        'notification' => 'warning',
                        'user' => 'info',
                        'integration' => 'primary',
                        'security' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'gray',
                        'integer' => 'info',
                        'boolean' => 'success',
                        'json' => 'warning',
                        'array' => 'primary',
                        'color' => 'danger',
                        'select' => 'secondary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Current Value')
                    ->formatStateUsing(function ($state, SystemSetting $record): string|HtmlString {
                        return match ($record->type) {
                            'boolean' => $record->typed_value 
                                ? new HtmlString('<span class="text-green-600 font-semibold">✓ Yes</span>')
                                : new HtmlString('<span class="text-red-600 font-semibold">✗ No</span>'),
                            'color' => new HtmlString("<div class='flex items-center gap-2'><div class='w-4 h-4 rounded border' style='background-color: {$state}'></div><span class='text-xs font-mono'>{$state}</span></div>"),
                            'json' => 'JSON (' . count(json_decode($state, true) ?: []) . ' items)',
                            'array' => 'Array (' . count($record->typed_value ?: []) . ' items)',
                            'integer' => number_format((int) $state),
                            default => Str::limit($state ?? '', 30),
                        };
                    })
                    ->tooltip(function (SystemSetting $record): ?string {
                        if (in_array($record->type, ['json', 'array'])) {
                            return json_encode($record->typed_value, JSON_PRETTY_PRINT);
                        }
                        return strlen($record->value ?? '') > 30 ? $record->value : null;
                    })
                    ->wrap(),
                
                Tables\Columns\ToggleColumn::make('is_public')
                    ->label('Public')
                    ->onIcon('heroicon-s-eye')
                    ->offIcon('heroicon-s-eye-slash')
                    ->onColor('success')
                    ->offColor('gray')
                    ->tooltip(fn (SystemSetting $record): string => 
                        $record->is_public ? 'Visible to all users' : 'Admin only'
                    ),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn (SystemSetting $record): string => 
                        'Created ' . $record->created_at->diffForHumans()
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(SystemSetting::ALLOWED_CATEGORIES)
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('type')
                    ->label('Data Type')
                    ->options(SystemSetting::ALLOWED_TYPES)
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Visibility')
                    ->placeholder('All settings')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),
                
                Tables\Filters\Filter::make('has_validation')
                    ->label('Has Validation Rules')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('validation_rules'))
                    ->toggle(),
                
                Tables\Filters\Filter::make('has_default')
                    ->label('Has Default Value')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('default_value'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Setting Details')
                    ->modalWidth('2xl'),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('copy_key')
                    ->label('Copy Key')
                    ->icon('heroicon-s-clipboard')
                    ->color('gray')
                    ->action(fn () => null) // Handled by frontend
                    ->extraAttributes(['onclick' => 'navigator.clipboard.writeText(this.dataset.key)'])
                    ->extraAttributes(fn (SystemSetting $record) => ['data-key' => $record->key]),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('toggle_public')
                        ->label('Toggle Public')
                        ->icon('heroicon-s-eye')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function (SystemSetting $record) {
                                $record->update(['is_public' => !$record->is_public]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Settings')
                        ->icon('heroicon-s-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $data = $records->map(function (SystemSetting $record) {
                                return [
                                    'key' => $record->key,
                                    'value' => $record->value,
                                    'category' => $record->category,
                                    'type' => $record->type,
                                    'description' => $record->description,
                                    'default_value' => $record->default_value,
                                    'is_public' => $record->is_public,
                                    'sort_order' => $record->sort_order,
                                ];
                            });
                            
                            return response()->streamDownload(function () use ($data) {
                                echo json_encode($data, JSON_PRETTY_PRINT);
                            }, 'settings_export_' . now()->format('Y-m-d_H-i-s') . '.json');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->searchDebounce('500ms')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession();
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
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
}
