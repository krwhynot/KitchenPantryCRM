<?php

namespace App\Filament\Resources\SystemSettingResource\Pages;

use App\Filament\Actions\SettingsExportAction;
use App\Filament\Actions\SettingsImportAction;
use App\Filament\Resources\SystemSettingResource;
use App\Services\SettingsService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSystemSettings extends ListRecords
{
    protected static string $resource = SystemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SettingsExportAction::make(),
            SettingsImportAction::make(),
            
            Actions\Action::make('setup_crm_defaults')
                ->label('Setup CRM Defaults')
                ->icon('heroicon-s-wrench-screwdriver')
                ->color('success')
                ->action(function (SettingsService $settingsService) {
                    // Initialize CRM default settings
                    $crmDefaults = [
                        'priority_levels' => $settingsService->getPriorityLevels(),
                        'sales_stages' => $settingsService->getSalesStages(),
                        'contact_roles' => $settingsService->getContactRoles(),
                        'interaction_types' => $settingsService->getInteractionTypes(),
                        'market_segments' => $settingsService->getMarketSegments(),
                        'distributor_options' => $settingsService->getDistributorOptions(),
                    ];
                    
                    $created = 0;
                    foreach ($crmDefaults as $key => $value) {
                        if (!$settingsService->get("crm.{$key}")) {
                            $settingsService->set("crm.{$key}", $value, 'json');
                            $created++;
                        }
                    }
                    
                    Notification::make()
                        ->title('CRM Defaults Initialized')
                        ->body("Created {$created} default CRM settings")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Initialize CRM Default Settings')
                ->modalDescription('This will create default CRM configuration settings if they don\'t already exist.')
                ->modalSubmitActionLabel('Initialize'),
            
            Actions\Action::make('cache_settings')
                ->label('Warm Cache')
                ->icon('heroicon-s-bolt')
                ->color('warning')
                ->action(function (SettingsService $settingsService) {
                    $settingsService->warmCache();
                    
                    Notification::make()
                        ->title('Cache Warmed')
                        ->body('Settings cache has been warmed for optimal performance')
                        ->success()
                        ->send();
                }),
            
            Actions\CreateAction::make(),
        ];
    }
}
