<?php

namespace App\Filament\Actions;

use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrganizationImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_organizations';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Organizations')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->form([
                FileUpload::make('import_file')
                    ->label('Organizations File')
                    ->acceptedFileTypes(['application/json', '.json', 'text/csv', '.csv'])
                    ->required()
                    ->maxSize(10240) // 10MB
                    ->helperText('Upload a JSON or CSV file with organization data'),
                
                Select::make('conflict_resolution')
                    ->label('Conflict Resolution Strategy')
                    ->options([
                        'overwrite' => 'Overwrite existing organizations',
                        'skip' => 'Skip conflicting organizations',
                        'merge' => 'Merge with existing data',
                    ])
                    ->default('skip')
                    ->required()
                    ->helperText('How to handle organizations that already exist (matched by name or email)'),
                
                Select::make('import_deleted')
                    ->label('Include Deleted Organizations')
                    ->options([
                        'ignore' => 'Ignore deleted organizations',
                        'restore' => 'Restore deleted organizations',
                        'keep_deleted' => 'Import as deleted',
                    ])
                    ->default('ignore')
                    ->helperText('How to handle deleted organizations in the import file'),
                
                Textarea::make('notes')
                    ->label('Import Notes')
                    ->placeholder('Optional notes about this import...')
                    ->rows(3),
            ])
            ->action(function (array $data) {
                return $this->importOrganizations($data);
            });
    }

    protected function importOrganizations(array $data)
    {
        try {
            // Read and validate the uploaded file
            $filePath = storage_path('app/' . $data['import_file']);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Uploaded file not found');
            }

            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if ($fileExtension === 'json') {
                $importData = $this->parseJsonFile($filePath);
            } elseif ($fileExtension === 'csv') {
                $importData = $this->parseCsvFile($filePath);
            } else {
                throw new \Exception('Unsupported file format. Please upload JSON or CSV files.');
            }

            // Validate the import data structure
            $this->validateImportData($importData);

            // Import the organizations
            $result = $this->processImport(
                $importData, 
                $data['conflict_resolution'],
                $data['import_deleted']
            );

            // Log the import activity
            activity('organizations')
                ->withProperties([
                    'imported_count' => $result['imported'],
                    'skipped_count' => $result['skipped'],
                    'updated_count' => $result['updated'],
                    'restored_count' => $result['restored'],
                    'conflict_resolution' => $data['conflict_resolution'],
                    'import_deleted' => $data['import_deleted'],
                    'notes' => $data['notes'] ?? null,
                ])
                ->log('Organizations imported from file');

            // Clean up the uploaded file
            unlink($filePath);

            // Show success notification
            Notification::make()
                ->title('Organizations Import Successful')
                ->body("Imported: {$result['imported']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}, Restored: {$result['restored']}")
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

    protected function parseJsonFile(string $filePath): array
    {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
        }

        // Handle both direct array of organizations and exported format
        if (isset($data['organizations'])) {
            return $data['organizations'];
        } elseif (isset($data[0])) {
            return $data;
        } else {
            throw new \Exception('Invalid JSON format. Expected array of organizations or export format.');
        }
    }

    protected function parseCsvFile(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open CSV file');
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception('CSV file appears to be empty or invalid');
        }

        $organizations = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $organizations[] = array_combine($headers, $row);
            }
        }

        fclose($handle);
        return $organizations;
    }

    protected function validateImportData(array $data): void
    {
        if (empty($data)) {
            throw new \Exception('No organization data found in import file');
        }

        foreach ($data as $index => $organization) {
            $validator = Validator::make($organization, [
                'name' => 'required|string|max:255',
                'priority' => 'nullable|in:A,B,C,D',
                'type' => 'nullable|in:PROSPECT,CLIENT,DISTRIBUTOR,SUPPLIER',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'status' => 'nullable|in:ACTIVE,INACTIVE,PROSPECT',
                'estimatedRevenue' => 'nullable|numeric|min:0',
                'employeeCount' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                $errors = implode(', ', $validator->errors()->all());
                throw new ValidationException($validator, "Validation failed for organization at row {$index}: {$errors}");
            }
        }
    }

    protected function processImport(array $organizations, string $conflictResolution, string $importDeleted): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $restored = 0;

        DB::transaction(function () use ($organizations, $conflictResolution, $importDeleted, &$imported, &$updated, &$skipped, &$restored) {
            foreach ($organizations as $orgData) {
                // Check for existing organization by name or email
                $query = Organization::withTrashed()
                    ->where('name', $orgData['name']);
                
                if (!empty($orgData['email'])) {
                    $query->orWhere('email', $orgData['email']);
                }
                
                $existing = $query->first();

                // Handle deleted organizations
                $isDeleted = isset($orgData['deleted_at']) && $orgData['deleted_at'];
                
                if ($existing) {
                    if ($existing->trashed()) {
                        // Handle trashed existing organization
                        if ($importDeleted === 'restore' && !$isDeleted) {
                            $existing->restore();
                            $existing->update($this->sanitizeOrgData($orgData));
                            $restored++;
                        } elseif ($importDeleted === 'keep_deleted' && $isDeleted) {
                            // Update but keep deleted
                            $existing->update($this->sanitizeOrgData($orgData));
                            $updated++;
                        } elseif ($importDeleted === 'ignore') {
                            $skipped++;
                        }
                    } else {
                        // Handle active existing organization
                        switch ($conflictResolution) {
                            case 'overwrite':
                                $existing->update($this->sanitizeOrgData($orgData));
                                if ($isDeleted && $importDeleted !== 'ignore') {
                                    $existing->delete();
                                }
                                $updated++;
                                break;
                            case 'skip':
                                $skipped++;
                                break;
                            case 'merge':
                                // Only update non-empty fields
                                $updateData = array_filter($this->sanitizeOrgData($orgData), fn($value) => !is_null($value) && $value !== '');
                                if (!empty($updateData)) {
                                    $existing->update($updateData);
                                    $updated++;
                                } else {
                                    $skipped++;
                                }
                                break;
                        }
                    }
                } else {
                    // Create new organization
                    $newOrg = Organization::create($this->sanitizeOrgData($orgData));
                    if ($isDeleted && $importDeleted === 'keep_deleted') {
                        $newOrg->delete();
                    }
                    $imported++;
                }
            }
        });

        return compact('imported', 'updated', 'skipped', 'restored');
    }

    protected function sanitizeOrgData(array $data): array
    {
        // Remove fields that shouldn't be mass assigned
        $sanitized = array_intersect_key($data, array_flip([
            'name', 'priority', 'segment', 'type', 'address', 'city', 'state', 'zipCode',
            'phone', 'email', 'website', 'notes', 'estimatedRevenue', 'employeeCount',
            'primaryContact', 'lastContactDate', 'nextFollowUpDate', 'status'
        ]));

        // Convert date strings to Carbon instances
        if (isset($sanitized['lastContactDate']) && $sanitized['lastContactDate']) {
            $sanitized['lastContactDate'] = \Carbon\Carbon::parse($sanitized['lastContactDate']);
        }
        
        if (isset($sanitized['nextFollowUpDate']) && $sanitized['nextFollowUpDate']) {
            $sanitized['nextFollowUpDate'] = \Carbon\Carbon::parse($sanitized['nextFollowUpDate']);
        }

        // Set defaults
        $sanitized['priority'] = $sanitized['priority'] ?? 'C';
        $sanitized['type'] = $sanitized['type'] ?? 'PROSPECT';
        $sanitized['status'] = $sanitized['status'] ?? 'ACTIVE';

        return $sanitized;
    }
}