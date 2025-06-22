<?php

namespace App\Filament\Resources\ProductLineResource\Pages;

use App\Filament\Resources\ProductLineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductLine extends EditRecord
{
    protected static string $resource = ProductLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
