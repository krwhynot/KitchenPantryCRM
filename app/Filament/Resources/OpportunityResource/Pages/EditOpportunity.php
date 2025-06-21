<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\OpportunityStageHistory;
use App\Models\Interaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewKanban')
                ->label('Pipeline View')
                ->icon('heroicon-o-view-columns')
                ->color('secondary')
                ->url(OpportunityResource::getUrl('kanban')),
                
            Actions\Action::make('stageTransition')
                ->label('Move Stage')
                ->icon('heroicon-o-arrow-right')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('new_stage')
                        ->label('Move to Stage')
                        ->options(\App\Models\Opportunity::getStageOptions())
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Transition Notes')
                        ->placeholder('Reason for stage change...'),
                ])
                ->action(function (array $data) {
                    $this->record->updateStage($data['new_stage'], $data['notes'] ?? null);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Stage Updated')
                        ->body("Opportunity moved to {$this->record->stage_label}")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
                
            Actions\DeleteAction::make(),
        ];
    }
}
