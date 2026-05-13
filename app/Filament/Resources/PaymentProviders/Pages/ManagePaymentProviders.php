<?php

namespace App\Filament\Resources\PaymentProviders\Pages;

use App\Filament\Resources\PaymentProviders\PaymentProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentProviders extends ManageRecords
{
    protected static string $resource = PaymentProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
