<?php

namespace App\Filament\Actions;

use App\Models\Contact;
use App\Models\Organization;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_contacts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Contacts')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->form([
                FileUpload::make('file')
                    ->label('Import File')
                    ->acceptedFileTypes(['application/json', 'text/csv', 'text/plain'])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->helperText('Upload a JSON or CSV file containing contact data.'),
                    
                Radio::make('conflict_resolution')
                    ->label('Conflict Resolution Strategy')
                    ->options([
                        'skip' => 'Skip Existing - Keep existing contacts unchanged',
                        'overwrite' => 'Overwrite - Replace existing contacts with imported data',
                        'merge' => 'Merge - Update only empty fields in existing contacts',
                    ])
                    ->default('skip')
                    ->required()
                    ->descriptions([
                        'skip' => 'Existing contacts will remain unchanged',
                        'overwrite' => 'Existing contacts will be completely replaced',
                        'merge' => 'Only empty fields will be updated',
                    ]),
                    
                Select::make('default_organization')
                    ->label('Default Organization (for CSV imports)')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Organization to assign to contacts when not specified in import file'),
                    
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
        
        $contacts = $data['contacts'] ?? $data;\n        
        if (!is_array($contacts)) {
            throw new \Exception('Invalid JSON structure. Expected contacts array.');
        }
        
        $this->processContacts($contacts, $options);
    }

    protected function importFromCsv(string $content, array $options): void
    {
        $lines = str_getcsv($content, \"\\n\");\n        $headers = str_getcsv(array_shift($lines));\n        \n        // Normalize headers\n        $headerMap = [\n            'first_name' => 'firstName',\n            'firstname' => 'firstName',\n            'last_name' => 'lastName',\n            'lastname' => 'lastName',\n            'email_address' => 'email',\n            'phone_number' => 'phone',\n            'job_title' => 'position',\n            'title' => 'position',\n            'is_primary' => 'isPrimary',\n            'primary' => 'isPrimary',\n            'organization_name' => 'organization_name',\n            'company' => 'organization_name',\n        ];\n        \n        $normalizedHeaders = array_map(function($header) use ($headerMap) {\n            $normalized = strtolower(trim(str_replace([' ', '-'], '_', $header)));\n            return $headerMap[$normalized] ?? $normalized;\n        }, $headers);\n        \n        $contacts = [];\n        foreach ($lines as $line) {\n            if (empty(trim($line))) continue;\n            \n            $values = str_getcsv($line);\n            $contact = array_combine($normalizedHeaders, $values);\n            \n            // Handle organization\n            if (!empty($contact['organization_name'])) {\n                $organization = Organization::firstOrCreate(\n                    ['name' => $contact['organization_name']],\n                    ['name' => $contact['organization_name']]\n                );\n                $contact['organization_id'] = $organization->id;\n            } elseif (!empty($options['default_organization'])) {\n                $contact['organization_id'] = $options['default_organization'];\n            }\n            \n            // Convert isPrimary\n            if (isset($contact['isPrimary'])) {\n                $contact['isPrimary'] = in_array(strtolower($contact['isPrimary']), ['true', '1', 'yes', 'primary']);\n            }\n            \n            $contacts[] = $contact;\n        }\n        \n        $this->processContacts($contacts, $options);\n    }\n\n    protected function processContacts(array $contacts, array $options): void\n    {\n        $imported = 0;\n        $updated = 0;\n        $skipped = 0;\n        $errors = [];\n        \n        DB::beginTransaction();\n        \n        try {\n            foreach ($contacts as $contactData) {\n                try {\n                    $result = $this->processContact($contactData, $options);\n                    \n                    switch ($result) {\n                        case 'imported':\n                            $imported++;\n                            break;\n                        case 'updated':\n                            $updated++;\n                            break;\n                        case 'skipped':\n                            $skipped++;\n                            break;\n                    }\n                } catch (\\Exception $e) {\n                    $errors[] = \"Contact {$contactData['firstName']} {$contactData['lastName']}: \" . $e->getMessage();\n                }\n            }\n            \n            DB::commit();\n            \n            $this->sendImportNotification($imported, $updated, $skipped, $errors);\n            \n        } catch (\\Exception $e) {\n            DB::rollBack();\n            throw $e;\n        }\n    }\n\n    protected function processContact(array $contactData, array $options): string\n    {\n        // Validate required fields\n        if (empty($contactData['firstName']) || empty($contactData['lastName'])) {\n            throw new \\Exception('First name and last name are required');\n        }\n        \n        // Check for existing contact by email or name+organization\n        $existingContact = null;\n        \n        if (!empty($contactData['email'])) {\n            $existingContact = Contact::where('email', $contactData['email'])->first();\n        }\n        \n        if (!$existingContact && !empty($contactData['organization_id'])) {\n            $existingContact = Contact::where('firstName', $contactData['firstName'])\n                ->where('lastName', $contactData['lastName'])\n                ->where('organization_id', $contactData['organization_id'])\n                ->first();\n        }\n        \n        $fillableData = [\n            'firstName' => $contactData['firstName'],\n            'lastName' => $contactData['lastName'],\n            'email' => $contactData['email'] ?? null,\n            'phone' => $contactData['phone'] ?? null,\n            'position' => $contactData['position'] ?? null,\n            'isPrimary' => $contactData['isPrimary'] ?? false,\n            'notes' => $contactData['notes'] ?? null,\n            'organization_id' => $contactData['organization_id'] ?? $options['default_organization'] ?? null,\n        ];\n        \n        // Remove null values\n        $fillableData = array_filter($fillableData, function($value) {\n            return $value !== null && $value !== '';\n        });\n        \n        if ($existingContact) {\n            switch ($options['conflict_resolution']) {\n                case 'skip':\n                    return 'skipped';\n                    \n                case 'overwrite':\n                    $existingContact->update($fillableData);\n                    return 'updated';\n                    \n                case 'merge':\n                    $mergeData = [];\n                    foreach ($fillableData as $key => $value) {\n                        if (empty($existingContact->$key)) {\n                            $mergeData[$key] = $value;\n                        }\n                    }\n                    if (!empty($mergeData)) {\n                        $existingContact->update($mergeData);\n                        return 'updated';\n                    }\n                    return 'skipped';\n            }\n        }\n        \n        // Create new contact\n        if (empty($fillableData['organization_id'])) {\n            throw new \\Exception('Organization is required for new contacts');\n        }\n        \n        Contact::create($fillableData);\n        return 'imported';\n    }\n\n    protected function sendImportNotification(int $imported, int $updated, int $skipped, array $errors): void\n    {\n        $total = $imported + $updated + $skipped;\n        \n        $message = \"Import completed: {$total} contacts processed.\\n\";\n        $message .= \"• {$imported} new contacts imported\\n\";\n        $message .= \"• {$updated} existing contacts updated\\n\";\n        $message .= \"• {$skipped} contacts skipped\\n\";\n        \n        if (!empty($errors)) {\n            $message .= \"\\nErrors encountered:\\n\" . implode(\"\\n\", array_slice($errors, 0, 5));\n            if (count($errors) > 5) {\n                $message .= \"\\n... and \" . (count($errors) - 5) . \" more errors.\";\n            }\n        }\n        \n        Notification::make()\n            ->title('Contact Import Results')\n            ->body($message)\n            ->success()\n            ->duration(10000)\n            ->send();\n    }\n}