<?php

namespace App\Filament\Resources\BarcodeParameters\Pages;

use App\Filament\Resources\BarcodeParameters\BarcodeParameterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBarcodeParameters extends ManageRecords
{
    protected static string $resource = BarcodeParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
