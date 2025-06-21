<?php

namespace App\Filament\Widgets;

use App\Models\Interaction;
use App\Models\Organization;
use App\Models\Contact;
use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;

class QuickInteractionWidget extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.widgets.quick-interaction-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'interactionDate' => now(),
            'type' => 'CALL',
            'duration' => 15,
            'priority' => 'medium',
            'user_id' => auth()->id(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('⚡ Quick Interaction Entry')
                    ->description('Log interactions in under 30 seconds')
                    ->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            Forms\Components\Select::make('organization_id')
                                ->label('Organization')
                                ->relationship('organization', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->searchDebounce(200)
                                ->optionsLimit(10)
                                ->placeholder('Start typing...')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if ($state) {
                                        $set('contact_id', null);
                                    }
                                }),
                                
                            Forms\Components\Select::make('contact_id')
                                ->label('Contact')
                                ->options(function (Get $get): array {
                                    $organizationId = $get('organization_id');
                                    if (!$organizationId) {
                                        return [];
                                    }
                                    return Contact::where('organization_id', $organizationId)
                                        ->get()
                                        ->pluck('full_name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select contact...')
                                ->searchDebounce(200),
                                
                            Forms\Components\Select::make('type')
                                ->label('Type')
                                ->options([
                                    'CALL' => 'Call',
                                    'EMAIL' => 'Email',
                                    'MEETING' => 'Meeting',
                                    'VISIT' => 'Visit',
                                ])
                                ->required()
                                ->default('CALL')
                                ->native(false),
                                
                            Forms\Components\TextInput::make('duration')
                                ->label('Duration (min)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(480)
                                ->default(15)
                                ->suffix('min'),
                        ]),
                        
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('subject')
                                ->label('Subject')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Brief subject line...')
                                ->autofocus(),
                                
                            Forms\Components\DateTimePicker::make('interactionDate')
                                ->label('Date & Time')
                                ->required()
                                ->default(now())
                                ->seconds(false),
                        ]),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Quick Notes')
                            ->rows(2)
                            ->placeholder('Key points from this interaction...')
                            ->columnSpanFull(),
                            
                        // Hidden fields
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                            
                        Forms\Components\Hidden::make('priority')
                            ->default('medium'),
                    ])
                    ->headerActions([
                        $this->duplicateLastAction(),
                    ]),
            ])
            ->statePath('data')
            ->model(Interaction::class);
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Log Interaction')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->keyBindings(['mod+enter'])
            ->action(function () {
                $this->create();
            });
    }

    public function createAndNewAction(): Action
    {
        return Action::make('createAndNew')
            ->label('Log & New')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->keyBindings(['mod+shift+enter'])
            ->action(function () {
                $this->createAndNew();
            });
    }

    public function duplicateLastAction(): Action
    {
        return Action::make('duplicateLast')
            ->label('Copy Last')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->size('sm')
            ->action(function () {
                $this->duplicateLastInteraction();
            });
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            Interaction::create($data);
            
            Notification::make()
                ->title('Interaction Logged! 🎉')
                ->body('Successfully logged in ' . round(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0), 1) . 's')
                ->success()
                ->duration(3000)
                ->send();
                
            $this->resetForm();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to log interaction: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createAndNew(): void
    {
        $data = $this->form->getState();
        
        try {
            Interaction::create($data);
            
            Notification::make()
                ->title('Interaction Logged! ⚡')
                ->body('Ready for next entry...')
                ->success()
                ->duration(2000)
                ->send();
                
            // Keep context for next entry
            $keepData = [
                'organization_id' => $data['organization_id'],
                'contact_id' => $data['contact_id'],
                'type' => $data['type'],
                'duration' => $data['duration'],
                'interactionDate' => now(),
                'user_id' => auth()->id(),
                'priority' => 'medium',
            ];
            
            $this->form->fill($keepData);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to log interaction: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function duplicateLastInteraction(): void
    {
        $lastInteraction = Interaction::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastInteraction) {
            $this->form->fill([
                'organization_id' => $lastInteraction->organization_id,
                'contact_id' => $lastInteraction->contact_id,
                'type' => $lastInteraction->type,
                'subject' => 'Follow-up: ' . $lastInteraction->subject,
                'duration' => $lastInteraction->duration,
                'priority' => $lastInteraction->priority,
                'interactionDate' => now(),
                'user_id' => auth()->id(),
            ]);
            
            Notification::make()
                ->title('Last Interaction Copied')
                ->body('Form pre-filled with your last interaction.')
                ->info()
                ->send();
        } else {
            Notification::make()
                ->title('No Previous Interactions')
                ->body('No previous interactions found.')
                ->warning()
                ->send();
        }
    }

    private function resetForm(): void
    {
        $this->form->fill([
            'interactionDate' => now(),
            'type' => 'CALL',
            'duration' => 15,
            'priority' => 'medium',
            'user_id' => auth()->id(),
            'subject' => '',
            'notes' => '',
            'organization_id' => null,
            'contact_id' => null,
        ]);
    }

    public function getRecentInteractions()
    {
        return Interaction::where('user_id', auth()->id())
            ->with(['organization', 'contact'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getTodaysStats()
    {
        $today = now()->startOfDay();
        
        return [
            'total' => Interaction::where('user_id', auth()->id())
                ->where('created_at', '>=', $today)
                ->count(),
            'calls' => Interaction::where('user_id', auth()->id())
                ->where('created_at', '>=', $today)
                ->where('type', 'CALL')
                ->count(),
            'meetings' => Interaction::where('user_id', auth()->id())
                ->where('created_at', '>=', $today)
                ->where('type', 'MEETING')
                ->count(),
        ];
    }
}