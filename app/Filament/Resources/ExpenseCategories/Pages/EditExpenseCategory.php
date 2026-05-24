<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseCategory extends EditRecord
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
