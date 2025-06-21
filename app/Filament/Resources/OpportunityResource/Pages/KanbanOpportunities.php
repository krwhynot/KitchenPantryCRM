<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class KanbanOpportunities extends Page
{
    protected static string $resource = OpportunityResource::class;

    protected static string $view = 'filament.resources.opportunity-resource.pages.kanban-opportunities';
    
    protected static ?string $title = 'Sales Pipeline Kanban';
    
    protected static ?string $navigationLabel = 'Pipeline';
    
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    
    public Collection $stages;
    public Collection $opportunities;
    
    public function mount(): void
    {
        $this->loadData();
    }
    
    public function loadData(): void
    {
        $this->stages = collect([
            ['id' => 'lead', 'title' => 'Lead', 'color' => 'bg-slate-100'],
            ['id' => 'prospect', 'title' => 'Prospect', 'color' => 'bg-yellow-100'],
            ['id' => 'proposal', 'title' => 'Proposal', 'color' => 'bg-blue-100'],
            ['id' => 'negotiation', 'title' => 'Negotiation', 'color' => 'bg-purple-100'],
            ['id' => 'closed', 'title' => 'Closed', 'color' => 'bg-green-100'],
        ]);
        
        $this->opportunities = Opportunity::with(['organization', 'contact', 'user'])
            ->where('isActive', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('stage');
    }
    
    #[On('opportunity-stage-updated')]
    public function refreshData(): void
    {
        $this->loadData();
    }
    
    public function moveOpportunity(string $opportunityId, string $newStage): void
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        
        if ($opportunity->stage !== $newStage) {
            $opportunity->updateStage($newStage, 'Moved via Kanban board');
            
            $this->loadData();
            
            Notification::make()
                ->title('Opportunity Updated')
                ->body("Opportunity moved to {$opportunity->stage_label}")
                ->success()
                ->send();
                
            $this->dispatch('opportunity-stage-updated');
        }
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Opportunity')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(OpportunityResource::getUrl('create')),
                
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->loadData()),
                
            Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->color('secondary')
                ->url(OpportunityResource::getUrl('index')),
        ];
    }
    
    public function getStageStats(string $stage): array
    {
        $stageOpportunities = $this->opportunities->get($stage, collect());
        
        return [
            'count' => $stageOpportunities->count(),
            'total_value' => $stageOpportunities->sum('value'),
            'weighted_value' => $stageOpportunities->sum(fn($opp) => $opp->weighted_value),
        ];
    }
    
    public function quickEditOpportunity(): Action
    {
        return Action::make('quickEdit')
            ->form([
                Forms\Components\Select::make('stage')
                    ->options(Opportunity::getStageOptions())
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\DatePicker::make('expectedCloseDate')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->placeholder('Update notes...'),
            ])
            ->fillForm(function (array $arguments): array {
                $opportunity = Opportunity::find($arguments['opportunity']);
                return $opportunity->toArray();
            })
            ->action(function (array $data, array $arguments): void {
                $opportunity = Opportunity::find($arguments['opportunity']);
                
                if ($opportunity->stage !== $data['stage']) {
                    $opportunity->updateStage($data['stage'], $data['notes'] ?? 'Updated via Kanban quick edit');
                } else {
                    $opportunity->update($data);
                }
                
                $this->loadData();
                
                Notification::make()
                    ->title('Opportunity Updated')
                    ->success()
                    ->send();
            });
    }
}