<?php

namespace App\Filament\Resources\BarcodeTypes\Pages;

use App\Filament\Resources\BarcodeTypes\BarcodeTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBarcodeTypes extends ManageRecords
{
    protected static string $resource = BarcodeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
