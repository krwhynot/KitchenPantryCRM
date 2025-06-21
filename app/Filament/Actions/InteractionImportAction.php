<?php

namespace App\Filament\Actions;

use App\Models\Interaction;
use App\Models\Organization;
use App\Models\Contact;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InteractionImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_interactions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Interactions')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->form([
                FileUpload::make('file')
                    ->label('Import File')
                    ->acceptedFileTypes(['application/json', 'text/csv', 'text/plain'])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->helperText('Upload a JSON or CSV file containing interaction data.'),
                    
                Radio::make('conflict_resolution')
                    ->label('Conflict Resolution Strategy')
                    ->options([
                        'skip' => 'Skip Existing - Keep existing interactions unchanged',
                        'overwrite' => 'Overwrite - Replace existing interactions with imported data',
                        'merge' => 'Merge - Update only empty fields in existing interactions',
                    ])
                    ->default('skip')
                    ->required()
                    ->descriptions([
                        'skip' => 'Existing interactions will remain unchanged',
                        'overwrite' => 'Existing interactions will be completely replaced',
                        'merge' => 'Only empty fields will be updated',
                    ]),
                    
                Select::make('default_organization')
                    ->label('Default Organization (for CSV imports)')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Organization to assign to interactions when not specified in import file'),
                    
                Textarea::make('preview')
                    ->label('Import Preview')
                    ->placeholder('Upload a file to see preview...')
                    ->disabled()
                    ->rows(4),
            ])
            ->action(function (array $data) {
                return $this->handleImport($data);
            });
    }

    protected function handleImport(array $data): void
    {
        try {
            $filePath = storage_path('app/public/' . $data['file']);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Upload file not found.');
            }
            
            $fileContent = file_get_contents($filePath);
            $fileExtension = strtolower(pathinfo($data['file'], PATHINFO_EXTENSION));
            
            if ($fileExtension === 'json') {
                $this->importFromJson($fileContent, $data);
            } else {
                $this->importFromCsv($fileContent, $data);
            }
            
            // Clean up uploaded file
            Storage::disk('public')->delete($data['file']);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function importFromJson(string $content, array $options): void
    {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        $interactions = $data['interactions'] ?? $data;
        
        if (!is_array($interactions)) {
            throw new \Exception('Invalid JSON structure. Expected interactions array.');
        }
        
        $this->processInteractions($interactions, $options);
    }

    protected function importFromCsv(string $content, array $options): void
    {
        $lines = str_getcsv($content, "\n");
        $headers = str_getcsv(array_shift($lines));
        
        // Normalize headers
        $headerMap = [
            'type' => 'type',
            'interaction_type' => 'type',
            'subject' => 'subject',
            'title' => 'subject',
            'date' => 'interactionDate',
            'interaction_date' => 'interactionDate',
            'date_time' => 'interactionDate',
            'duration' => 'duration',
            'duration_minutes' => 'duration',
            'notes' => 'notes',
            'description' => 'notes',
            'outcome' => 'outcome',
            'result' => 'outcome',
            'priority' => 'priority',
            'next_action' => 'nextAction',
            'follow_up_date' => 'follow_up_date',
            'followup_date' => 'follow_up_date',
            'organization' => 'organization_name',
            'organization_name' => 'organization_name',
            'company' => 'organization_name',
            'contact_name' => 'contact_name',
            'contact' => 'contact_name',
        ];
        
        $normalizedHeaders = array_map(function($header) use ($headerMap) {
            $normalized = strtolower(trim(str_replace([' ', '-'], '_', $header)));
            return $headerMap[$normalized] ?? $normalized;
        }, $headers);
        
        $interactions = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $values = str_getcsv($line);
            $interaction = array_combine($normalizedHeaders, $values);
            
            // Handle organization
            if (!empty($interaction['organization_name'])) {
                $organization = Organization::where('name', $interaction['organization_name'])->first();
                if ($organization) {
                    $interaction['organization_id'] = $organization->id;
                }
            } elseif (!empty($options['default_organization'])) {
                $interaction['organization_id'] = $options['default_organization'];
            }
            
            // Handle contact by name
            if (!empty($interaction['contact_name']) && !empty($interaction['organization_id'])) {
                $contactParts = explode(' ', $interaction['contact_name'], 2);
                $firstName = $contactParts[0] ?? '';
                $lastName = $contactParts[1] ?? '';
                
                $contact = Contact::where('organization_id', $interaction['organization_id'])
                    ->where('firstName', $firstName)
                    ->when($lastName, function($query) use ($lastName) {
                        return $query->where('lastName', $lastName);
                    })
                    ->first();
                    
                if ($contact) {
                    $interaction['contact_id'] = $contact->id;
                }
            }
            
            // Parse date
            if (!empty($interaction['interactionDate'])) {
                try {
                    $interaction['interactionDate'] = Carbon::parse($interaction['interactionDate'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $interaction['interactionDate'] = now()->format('Y-m-d H:i:s');
                }
            }
            
            // Normalize type
            if (!empty($interaction['type'])) {
                $interaction['type'] = strtoupper($interaction['type']);
                if (!in_array($interaction['type'], ['CALL', 'EMAIL', 'MEETING', 'VISIT'])) {
                    $interaction['type'] = 'CALL'; // Default fallback
                }
            }
            
            // Normalize outcome
            if (!empty($interaction['outcome'])) {
                $interaction['outcome'] = strtoupper($interaction['outcome']);
                if (!in_array($interaction['outcome'], ['POSITIVE', 'NEUTRAL', 'NEGATIVE', 'FOLLOWUPNEEDED'])) {
                    $interaction['outcome'] = null;
                }
            }
            
            // Set user_id
            $interaction['user_id'] = auth()->id();
            
            $interactions[] = $interaction;
        }
        
        $this->processInteractions($interactions, $options);
    }

    protected function processInteractions(array $interactions, array $options): void
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($interactions as $interactionData) {
                try {
                    $result = $this->processInteraction($interactionData, $options);
                    
                    switch ($result) {
                        case 'imported':
                            $imported++;
                            break;
                        case 'updated':
                            $updated++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Interaction {$interactionData['subject'] ?? 'Unknown'}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $this->sendImportNotification($imported, $updated, $skipped, $errors);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function processInteraction(array $interactionData, array $options): string
    {
        // Validate required fields
        if (empty($interactionData['subject']) || empty($interactionData['type'])) {
            throw new \Exception('Subject and type are required');
        }
        
        // Check for existing interaction by subject and date
        $existingInteraction = null;
        
        if (!empty($interactionData['subject']) && !empty($interactionData['interactionDate'])) {
            $existingInteraction = Interaction::where('subject', $interactionData['subject'])
                ->whereDate('interactionDate', Carbon::parse($interactionData['interactionDate'])->format('Y-m-d'))
                ->first();
        }
        
        $fillableData = [
            'type' => $interactionData['type'] ?? 'CALL',
            'subject' => $interactionData['subject'],
            'notes' => $interactionData['notes'] ?? null,
            'interactionDate' => $interactionData['interactionDate'] ?? now(),
            'duration' => is_numeric($interactionData['duration'] ?? null) ? (int)$interactionData['duration'] : null,
            'outcome' => $interactionData['outcome'] ?? null,
            'priority' => $interactionData['priority'] ?? 'medium',
            'nextAction' => $interactionData['nextAction'] ?? null,
            'follow_up_date' => $interactionData['follow_up_date'] ?? null,
            'organization_id' => $interactionData['organization_id'] ?? $options['default_organization'] ?? null,
            'contact_id' => $interactionData['contact_id'] ?? null,
            'user_id' => $interactionData['user_id'] ?? auth()->id(),
        ];
        
        // Remove null values
        $fillableData = array_filter($fillableData, function($value) {
            return $value !== null && $value !== '';
        });
        
        if ($existingInteraction) {
            switch ($options['conflict_resolution']) {
                case 'skip':
                    return 'skipped';
                    
                case 'overwrite':
                    $existingInteraction->update($fillableData);
                    return 'updated';
                    
                case 'merge':
                    $mergeData = [];
                    foreach ($fillableData as $key => $value) {
                        if (empty($existingInteraction->$key)) {
                            $mergeData[$key] = $value;
                        }
                    }
                    if (!empty($mergeData)) {
                        $existingInteraction->update($mergeData);
                        return 'updated';
                    }
                    return 'skipped';
            }
        }
        
        // Create new interaction
        if (empty($fillableData['organization_id'])) {
            throw new \Exception('Organization is required for new interactions');
        }
        
        Interaction::create($fillableData);
        return 'imported';
    }

    protected function sendImportNotification(int $imported, int $updated, int $skipped, array $errors): void
    {
        $total = $imported + $updated + $skipped;
        
        $message = "Import completed: {$total} interactions processed.\n";
        $message .= "• {$imported} new interactions imported\n";
        $message .= "• {$updated} existing interactions updated\n";
        $message .= "• {$skipped} interactions skipped\n";
        
        if (!empty($errors)) {
            $message .= "\nErrors encountered:\n" . implode("\n", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= "\n... and " . (count($errors) - 5) . " more errors.";
            }
        }
        
        Notification::make()
            ->title('Interaction Import Results')
            ->body($message)
            ->success()
            ->duration(10000)
            ->send();
    }
}