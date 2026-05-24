<?php

namespace App\Filament\Resources\Payments\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
