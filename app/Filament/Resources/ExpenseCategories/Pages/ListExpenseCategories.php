<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
