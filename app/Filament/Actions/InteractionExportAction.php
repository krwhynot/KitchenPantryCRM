<?php

namespace App\Filament\Actions;

use App\Models\Interaction;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class InteractionExportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export_interactions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Interactions')
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
                    
                Select::make('date_range')
                    ->label('Date Range')
                    ->options([
                        'all' => 'All Interactions',
                        'last_30' => 'Last 30 Days',
                        'last_90' => 'Last 90 Days',
                        'this_year' => 'This Year',
                        'custom' => 'Custom Range',
                    ])
                    ->default('last_30')
                    ->live()
                    ->required(),
                    
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->visible(fn ($get) => $get('date_range') === 'custom')
                    ->required(fn ($get) => $get('date_range') === 'custom'),
                    
                DatePicker::make('end_date')
                    ->label('End Date')
                    ->visible(fn ($get) => $get('date_range') === 'custom')
                    ->required(fn ($get) => $get('date_range') === 'custom'),
                    
                Select::make('organization_filter')
                    ->label('Filter by Organization (Optional)')
                    ->relationship('organization', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                    
                Select::make('type_filter')
                    ->label('Filter by Type (Optional)')
                    ->options([
                        'CALL' => 'Phone Call',
                        'EMAIL' => 'Email',
                        'MEETING' => 'Meeting',
                        'VISIT' => 'Site Visit',
                    ])
                    ->multiple(),
                    
                Checkbox::make('include_notes')
                    ->label('Include Notes')
                    ->default(true),
                    
                Checkbox::make('my_interactions_only')
                    ->label('My Interactions Only')
                    ->default(false),
            ])
            ->action(function (array $data) {
                return $this->handleExport($data);
            });
    }

    protected function handleExport(array $data): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Build query with filters
        $query = Interaction::query()->with(['organization', 'contact', 'user']);
        
        // Apply date range filter
        switch ($data['date_range']) {
            case 'last_30':
                $query->where('interactionDate', '>=', now()->subDays(30));
                break;
            case 'last_90':
                $query->where('interactionDate', '>=', now()->subDays(90));
                break;
            case 'this_year':
                $query->whereYear('interactionDate', now()->year);
                break;
            case 'custom':
                if (!empty($data['start_date'])) {
                    $query->where('interactionDate', '>=', $data['start_date']);
                }
                if (!empty($data['end_date'])) {
                    $query->where('interactionDate', '<=', $data['end_date']);
                }
                break;
        }
        
        // Apply organization filter
        if (!empty($data['organization_filter'])) {
            $query->whereIn('organization_id', $data['organization_filter']);
        }
        
        // Apply type filter
        if (!empty($data['type_filter'])) {
            $query->whereIn('type', $data['type_filter']);
        }
        
        // Apply user filter
        if ($data['my_interactions_only']) {
            $query->where('user_id', auth()->id());
        }
        
        $interactions = $query->orderBy('interactionDate', 'desc')->get();
        
        if ($data['format'] === 'json') {
            return $this->exportAsJson($interactions, $data);
        } else {
            return $this->exportAsCsv($interactions, $data);
        }
    }

    protected function exportAsJson($interactions, array $options): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $exportData = [
            'metadata' => [
                'exported_at' => now()->toISOString(),
                'total_interactions' => $interactions->count(),
                'exported_by' => auth()->user()?->name ?? 'System',
                'date_range' => $options['date_range'],
                'format_version' => '1.0',
                'filters' => [
                    'organizations' => $options['organization_filter'] ?? [],
                    'types' => $options['type_filter'] ?? [],
                    'my_interactions_only' => $options['my_interactions_only'],
                ],
            ],
            'interactions' => $interactions->map(function ($interaction) use ($options) {
                $data = [
                    'id' => $interaction->id,
                    'type' => $interaction->type,
                    'subject' => $interaction->subject,
                    'interactionDate' => $interaction->interactionDate?->toISOString(),
                    'duration' => $interaction->duration,
                    'outcome' => $interaction->outcome,
                    'priority' => $interaction->priority,
                    'nextAction' => $interaction->nextAction,
                    'follow_up_date' => $interaction->follow_up_date?->toISOString(),
                    'organization' => [
                        'id' => $interaction->organization?->id,
                        'name' => $interaction->organization?->name,
                    ],
                    'contact' => [
                        'id' => $interaction->contact?->id,
                        'name' => $interaction->contact?->full_name,
                        'email' => $interaction->contact?->email,
                        'position' => $interaction->contact?->position,
                    ],
                    'user' => [
                        'id' => $interaction->user?->id,
                        'name' => $interaction->user?->name,
                    ],
                    'created_at' => $interaction->created_at?->toISOString(),
                    'updated_at' => $interaction->updated_at?->toISOString(),
                ];
                
                if ($options['include_notes']) {
                    $data['notes'] = $interaction->notes;
                }
                
                return $data;
            })->values(),
        ];

        $filename = 'interactions_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = 'exports/' . $filename;
        
        Storage::disk('local')->put($path, json_encode($exportData, JSON_PRETTY_PRINT));
        
        Notification::make()
            ->title('Export Completed')
            ->body("Successfully exported {$interactions->count()} interactions to {$filename}")
            ->success()
            ->send();

        return response()->download(storage_path('app/' . $path), $filename)->deleteFileAfterSend();
    }

    protected function exportAsCsv($interactions, array $options): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = 'interactions_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;
        
        $csvData = [];
        
        // Headers
        $headers = [
            'ID',
            'Type',
            'Subject',
            'Date & Time',
            'Duration (min)',
            'Outcome',
            'Priority',
            'Organization',
            'Contact Name',
            'Contact Email',
            'Contact Position',
            'Created By',
            'Next Action',
            'Follow-up Date',
            'Created At',
        ];
        
        if ($options['include_notes']) {
            $headers[] = 'Notes';
        }
        
        $csvData[] = $headers;
        
        // Data rows
        foreach ($interactions as $interaction) {
            $row = [
                $interaction->id,
                $interaction->type_label,
                $interaction->subject,
                $interaction->interactionDate?->format('Y-m-d H:i:s'),
                $interaction->duration,
                $interaction->outcome_label,
                $interaction->priority_label,
                $interaction->organization?->name,
                $interaction->contact?->full_name,
                $interaction->contact?->email,
                $interaction->contact?->position,
                $interaction->user?->name,
                $interaction->nextAction,
                $interaction->follow_up_date?->format('Y-m-d'),
                $interaction->created_at?->format('Y-m-d H:i:s'),
            ];
            
            if ($options['include_notes']) {
                $row[] = $interaction->notes;
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
            ->body("Successfully exported {$interactions->count()} interactions to {$filename}")
            ->success()
            ->send();

        return response()->download(storage_path('app/' . $path), $filename)->deleteFileAfterSend();
    }
}