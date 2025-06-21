<?php

namespace App\Filament\Components;

use App\Models\Interaction;
use App\Models\Organization;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Livewire\Component;
use Filament\Forms\Get;
use Filament\Forms\Set;

class QuickInteractionModal extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    public bool $isOpen = false;
    public ?string $contextType = null;
    public ?string $contextId = null;

    public function mount(?string $contextType = null, ?string $contextId = null): void
    {
        $this->contextType = $contextType;
        $this->contextId = $contextId;
        
        $this->form->fill([
            'interactionDate' => now(),
            'type' => 'CALL',
            'duration' => 15,
            'priority' => 'medium',
            'user_id' => auth()->id(),
            // Context-based pre-filling
            'organization_id' => $contextType === 'organization' ? $contextId : null,
            'contact_id' => $contextType === 'contact' ? $contextId : null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Lightning-fast entry form - optimized for 15-20 second completion
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->searchDebounce(200)
                            ->optionsLimit(15)
                            ->placeholder('Type to search...')
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    // Auto-populate contacts for selected organization
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
                    ]),
                    
                Forms\Components\Grid::make(3)
                    ->schema([
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
                            
                        Forms\Components\DateTimePicker::make('interactionDate')
                            ->label('Date & Time')
                            ->required()
                            ->default(now())
                            ->seconds(false),
                            
                        Forms\Components\TextInput::make('duration')
                            ->label('Duration (min)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(480)
                            ->default(15)
                            ->suffix('min'),
                    ]),
                    
                Forms\Components\TextInput::make('subject')
                    ->label('Subject')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Brief subject line...')
                    ->autofocus()
                    ->columnSpanFull(),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Quick Notes')
                    ->rows(3)
                    ->placeholder('Key points from this interaction...')
                    ->columnSpanFull(),
                    
                // Hidden fields with smart defaults
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                    
                Forms\Components\Hidden::make('priority')
                    ->default('medium'),
            ])
            ->statePath('data')
            ->model(Interaction::class);
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Save Interaction')
            ->color('primary')
            ->keyBindings(['mod+s'])
            ->action(function () {
                $this->create();
            });
    }

    public function createAndNewAction(): Action
    {
        return Action::make('createAndNew')
            ->label('Save & Add Another')
            ->color('success')
            ->keyBindings(['mod+shift+s'])
            ->action(function () {
                $this->createAndNew();
            });
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->keyBindings(['escape'])
            ->action(function () {
                $this->cancel();
            });
    }

    public function duplicateLastAction(): Action
    {
        return Action::make('duplicateLast')
            ->label('Duplicate Last')
            ->color('warning')
            ->icon('heroicon-o-document-duplicate')
            ->action(function () {
                $this->duplicateLastInteraction();
            });
    }

    public function open(): void
    {
        $this->isOpen = true;
        $this->dispatch('open-modal');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->dispatch('close-modal');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            Interaction::create($data);
            
            Notification::make()
                ->title('Interaction Created')
                ->body('Interaction logged successfully!')
                ->success()
                ->duration(3000)
                ->send();
                
            $this->close();
            $this->redirect(request()->header('Referer'));
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to create interaction: ' . $e->getMessage())
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
                ->title('Interaction Created')
                ->body('Interaction logged! Ready for next entry.')
                ->success()
                ->duration(2000)
                ->send();
                
            // Keep organization and contact for next entry
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
                ->body('Failed to create interaction: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancel(): void
    {
        $this->form->fill([
            'interactionDate' => now(),
            'type' => 'CALL',
            'duration' => 15,
            'priority' => 'medium',
            'user_id' => auth()->id(),
        ]);
        $this->close();
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
                ->title('Last Interaction Duplicated')
                ->body('Form populated with your last interaction details.')
                ->info()
                ->send();
        } else {
            Notification::make()
                ->title('No Previous Interactions')
                ->body('No previous interactions found to duplicate.')
                ->warning()
                ->send();
        }
    }

    public function render()
    {
        return view('filament.components.quick-interaction-modal');
    }
}