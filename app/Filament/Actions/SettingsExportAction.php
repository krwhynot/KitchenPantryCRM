<?php

namespace App\Filament\Actions;

use App\Models\SystemSetting;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Response;

class SettingsExportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export_settings';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Settings')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function () {
                return $this->exportSettings();
            });
    }

    protected function exportSettings()
    {
        $settings = SystemSetting::orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get()
            ->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'category' => $setting->category,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'default_value' => $setting->default_value,
                    'validation_rules' => $setting->validation_rules,
                    'ui_component' => $setting->ui_component,
                    'is_public' => $setting->is_public,
                    'sort_order' => $setting->sort_order,
                ];
            });

        $exportData = [
            'version' => 1,
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()?->name ?? 'System',
            'total_settings' => $settings->count(),
            'settings' => $settings->toArray(),
        ];

        $filename = 'settings-export-' . now()->format('Y-m-d-H-i-s') . '.json';
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}