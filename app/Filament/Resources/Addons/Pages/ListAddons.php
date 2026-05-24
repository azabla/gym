<?php

namespace App\Filament\Resources\Addons\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Addons\AddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAddons extends ListRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
