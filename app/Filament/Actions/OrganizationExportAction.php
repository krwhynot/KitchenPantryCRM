<?php

namespace App\Filament\Actions;

use App\Models\Organization;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Response;

class OrganizationExportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export_organizations';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export Organizations')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function () {
                return $this->exportOrganizations();
            });
    }

    protected function exportOrganizations()
    {
        $organizations = Organization::withTrashed()
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(function ($organization) {
                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'priority' => $organization->priority,
                    'segment' => $organization->segment,
                    'type' => $organization->type,
                    'address' => $organization->address,
                    'city' => $organization->city,
                    'state' => $organization->state,
                    'zipCode' => $organization->zipCode,
                    'phone' => $organization->phone,
                    'email' => $organization->email,
                    'website' => $organization->website,
                    'notes' => $organization->notes,
                    'estimatedRevenue' => $organization->estimatedRevenue,
                    'employeeCount' => $organization->employeeCount,
                    'primaryContact' => $organization->primaryContact,
                    'lastContactDate' => $organization->lastContactDate?->toISOString(),
                    'nextFollowUpDate' => $organization->nextFollowUpDate?->toISOString(),
                    'status' => $organization->status,
                    'deleted_at' => $organization->deleted_at?->toISOString(),
                    'created_at' => $organization->created_at->toISOString(),
                    'updated_at' => $organization->updated_at->toISOString(),
                ];
            });

        $exportData = [
            'version' => 1,
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()?->name ?? 'System',
            'total_organizations' => $organizations->count(),
            'active_organizations' => $organizations->where('deleted_at', null)->count(),
            'deleted_organizations' => $organizations->where('deleted_at', '!=', null)->count(),
            'organizations' => $organizations->toArray(),
        ];

        $filename = 'organizations-export-' . now()->format('Y-m-d-H-i-s') . '.json';
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}