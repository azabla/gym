<?php

namespace App\Filament\Resources\Members\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
