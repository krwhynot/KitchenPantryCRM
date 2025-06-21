<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Actions\OrganizationExportAction;
use App\Filament\Actions\OrganizationImportAction;
use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OrganizationExportAction::make(),
            OrganizationImportAction::make(),
            Actions\CreateAction::make(),
        ];
    }
}
