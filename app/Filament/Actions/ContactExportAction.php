<?php

namespace App\Filament\Actions;

use App\Models\Contact;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ContactExportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export_contacts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Contacts')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->form([
                Select::make('format')
                    ->label('Export Format')
                    ->options([
                        'json' => 'JSON (Complete Data)',
                        'csv' => 'CSV (Basic Data)',
                    ])
                    ->default('json')
                    ->required(),
                    
                Checkbox::make('include_deleted')
                    ->label('Include Soft Deleted Contacts')
                    ->default(false),
                    
                Checkbox::make('include_notes')
                    ->label('Include Notes')
                    ->default(true),
                    
                Select::make('organization_filter')
                    ->label('Filter by Organization (Optional)')
                    ->relationship('organization', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->action(function (array $data) {
                return $this->handleExport($data);
            });
    }

    protected function handleExport(array $data): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Build query with filters
        $query = Contact::query();
        
        if ($data['include_deleted']) {
            $query->withTrashed();
        }
        
        if (!empty($data['organization_filter'])) {
            $query->whereIn('organization_id', $data['organization_filter']);
        }
        
        $contacts = $query->with(['organization'])->get();
        
        if ($data['format'] === 'json') {
            return $this->exportAsJson($contacts, $data);
        } else {
            return $this->exportAsCsv($contacts, $data);
        }
    }

    protected function exportAsJson($contacts, array $options): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $exportData = [
            'metadata' => [
                'exported_at' => now()->toISOString(),
                'total_contacts' => $contacts->count(),
                'exported_by' => auth()->user()?->name ?? 'System',
                'format_version' => '1.0',
                'options' => $options,
            ],
            'contacts' => $contacts->map(function ($contact) use ($options) {
                $data = [
                    'id' => $contact->id,
                    'firstName' => $contact->firstName,
                    'lastName' => $contact->lastName,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'position' => $contact->position,
                    'isPrimary' => $contact->isPrimary,
                    'organization' => [
                        'id' => $contact->organization?->id,
                        'name' => $contact->organization?->name,
                        'email' => $contact->organization?->email,
                        'phone' => $contact->organization?->phone,
                    ],
                    'created_at' => $contact->created_at?->toISOString(),
                    'updated_at' => $contact->updated_at?->toISOString(),
                ];
                
                if ($options['include_notes']) {
                    $data['notes'] = $contact->notes;
                }
                
                if ($options['include_deleted'] && $contact->trashed()) {
                    $data['deleted_at'] = $contact->deleted_at?->toISOString();
                }
                
                return $data;
            })->values(),
        ];

        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = 'exports/' . $filename;
        
        Storage::disk('local')->put($path, json_encode($exportData, JSON_PRETTY_PRINT));
        
        Notification::make()
            ->title('Export Completed')
            ->body("Successfully exported {$contacts->count()} contacts to {$filename}")
            ->success()
            ->send();

        return response()->download(storage_path('app/' . $path), $filename)->deleteFileAfterSend();
    }

    protected function exportAsCsv($contacts, array $options): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;
        
        $csvData = [];
        
        // Headers
        $headers = [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Position',
            'Is Primary',
            'Organization ID',
            'Organization Name',
            'Organization Email',
            'Organization Phone',
            'Created At',
            'Updated At',
        ];
        
        if ($options['include_notes']) {
            $headers[] = 'Notes';
        }
        
        if ($options['include_deleted']) {
            $headers[] = 'Deleted At';
        }
        
        $csvData[] = $headers;
        
        // Data rows
        foreach ($contacts as $contact) {
            $row = [
                $contact->id,
                $contact->firstName,
                $contact->lastName,
                $contact->email,
                $contact->phone,
                $contact->position,
                $contact->isPrimary ? 'Yes' : 'No',
                $contact->organization?->id,
                $contact->organization?->name,
                $contact->organization?->email,
                $contact->organization?->phone,
                $contact->created_at?->format('Y-m-d H:i:s'),
                $contact->updated_at?->format('Y-m-d H:i:s'),
            ];
            
            if ($options['include_notes']) {
                $row[] = $contact->notes;
            }
            
            if ($options['include_deleted']) {
                $row[] = $contact->deleted_at?->format('Y-m-d H:i:s');
            }
            
            $csvData[] = $row;
        }
        
        // Write CSV file
        $handle = fopen(storage_path('app/' . $path), 'w');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        
        Notification::make()
            ->title('Export Completed')
            ->body("Successfully exported {$contacts->count()} contacts to {$filename}")
            ->success()
            ->send();

        return response()->download(storage_path('app/' . $path), $filename)->deleteFileAfterSend();
    }
}