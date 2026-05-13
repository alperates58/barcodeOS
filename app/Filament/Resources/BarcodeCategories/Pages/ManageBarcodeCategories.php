<?php

namespace App\Filament\Resources\BarcodeCategories\Pages;

use App\Filament\Resources\BarcodeCategories\BarcodeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBarcodeCategories extends ManageRecords
{
    protected static string $resource = BarcodeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
