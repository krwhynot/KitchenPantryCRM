<?php

namespace App\Filament\Actions;

use App\Models\SystemSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_settings';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Settings')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->form([
                FileUpload::make('import_file')
                    ->label('Settings File')
                    ->acceptedFileTypes(['application/json', '.json'])
                    ->required()
                    ->maxSize(5120) // 5MB
                    ->helperText('Upload a JSON file exported from this system'),
                
                Select::make('conflict_resolution')
                    ->label('Conflict Resolution Strategy')
                    ->options([
                        'overwrite' => 'Overwrite existing settings',
                        'skip' => 'Skip conflicting settings',
                        'merge' => 'Merge where possible',
                    ])
                    ->default('overwrite')
                    ->required()
                    ->helperText('How to handle settings that already exist'),
                
                Textarea::make('notes')
                    ->label('Import Notes')
                    ->placeholder('Optional notes about this import...')
                    ->rows(3),
            ])
            ->action(function (array $data) {
                return $this->importSettings($data);
            });
    }

    protected function importSettings(array $data)
    {
        try {
            // Read and validate the uploaded file
            $filePath = storage_path('app/' . $data['import_file']);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Uploaded file not found');
            }

            $jsonContent = file_get_contents($filePath);
            $importData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
            }

            // Validate the import data structure
            $this->validateImportData($importData);

            // Import the settings
            $result = $this->processImport($importData['settings'], $data['conflict_resolution']);

            // Log the import activity
            activity('settings')
                ->withProperties([
                    'imported_count' => $result['imported'],
                    'skipped_count' => $result['skipped'],
                    'updated_count' => $result['updated'],
                    'conflict_resolution' => $data['conflict_resolution'],
                    'notes' => $data['notes'] ?? null,
                ])
                ->log('Settings imported from file');

            // Clean up the uploaded file
            unlink($filePath);

            // Show success notification
            Notification::make()
                ->title('Settings Import Successful')
                ->body("Imported: {$result['imported']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}")
                ->success()
                ->send();

            return $result;

        } catch (\Exception $e) {
            // Clean up the uploaded file on error
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function validateImportData(array $data): void
    {
        $validator = Validator::make($data, [
            'version' => 'required|integer|min:1',
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string',
            'settings.*.category' => 'required|string|max:255',
            'settings.*.type' => 'required|string|max:255',
            'settings.*.description' => 'nullable|string',
            'settings.*.is_public' => 'boolean',
            'settings.*.sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate version compatibility
        if ($data['version'] > 1) {
            throw new \Exception('This import file version is not supported. Please update the application.');
        }

        // Validate setting types and categories
        foreach ($data['settings'] as $index => $setting) {
            if (!array_key_exists($setting['type'], SystemSetting::ALLOWED_TYPES)) {
                throw new \Exception("Invalid setting type '{$setting['type']}' at index {$index}");
            }

            if (!array_key_exists($setting['category'], SystemSetting::ALLOWED_CATEGORIES)) {
                throw new \Exception("Invalid setting category '{$setting['category']}' at index {$index}");
            }
        }
    }

    protected function processImport(array $settings, string $conflictResolution): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($settings, $conflictResolution, &$imported, &$updated, &$skipped) {
            foreach ($settings as $settingData) {
                $existing = SystemSetting::where('key', $settingData['key'])->first();

                if ($existing) {
                    switch ($conflictResolution) {
                        case 'overwrite':
                            $existing->update($settingData);
                            $updated++;
                            break;
                        case 'skip':
                            $skipped++;
                            break;
                        case 'merge':
                            // For merge, only update if the imported value is different
                            if ($existing->value !== $settingData['value']) {
                                $existing->update($settingData);
                                $updated++;
                            } else {
                                $skipped++;
                            }
                            break;
                    }
                } else {
                    SystemSetting::create($settingData);
                    $imported++;
                }
            }
        });

        return compact('imported', 'updated', 'skipped');
    }
}